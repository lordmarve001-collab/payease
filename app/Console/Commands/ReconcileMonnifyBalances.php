<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\MmoProviderSettingService;
use App\Services\MonnifyClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReconcileMonnifyBalances extends Command
{
    protected $signature = 'payease:reconcile-monnify-balances';

    protected $description = 'Compare local wallet balances against Monnify reported balances and flag mismatches to audit_logs';

    public function handle(MmoProviderSettingService $providerSettingService): int
    {
        $setting = $providerSettingService->getProviderSetting('monnify');

        if (!is_array($setting->credentials) || empty($setting->credentials)) {
            $this->error('Monnify credentials are not configured.');

            return Command::FAILURE;
        }

        $client = new MonnifyClient($setting->credentials, (string) $setting->environment);

        try {
            $authResult = $client->testConnection();
            if (($authResult['status'] ?? '') !== 'success') {
                $this->error('Monnify authentication failed: ' . ($authResult['message'] ?? 'Unknown error'));

                return Command::FAILURE;
            }
        } catch (\Throwable $throwable) {
            $this->error('Monnify connection failed: ' . $throwable->getMessage());

            return Command::FAILURE;
        }

        $wallets = Wallet::query()
            ->where('status', 'active')
            ->where('mmo_partner', 'monnify')
            ->whereNotNull('mmo_wallet_id')
            ->get();

        $this->info("Checking {$wallets->count()} active Monnify-linked wallets...");

        $mismatches = 0;
        $checked = 0;
        $skipped = 0;

        foreach ($wallets as $wallet) {
            $checked++;

            try {
                $monnifyBalance = $client->getBalance((string) $wallet->provider_reference);
                $localBalance = round((float) $wallet->available_balance, 2);
                $difference = round($monnifyBalance - $localBalance, 2);

                if (abs($difference) > 0.01) {
                    $mismatches++;

                    AuditLog::create([
                        'user_id' => null,
                        'action' => 'balance_mismatch',
                        'entity_type' => 'wallet',
                        'entity_id' => $wallet->id,
                        'old_values' => [
                            'local_balance' => $localBalance,
                        ],
                        'new_values' => [
                            'monnify_balance' => $monnifyBalance,
                            'difference' => $difference,
                            'wallet_mmo_id' => $wallet->mmo_wallet_id,
                            'account_number' => $wallet->account_number,
                            'user_id' => $wallet->user_id,
                        ],
                        'ip_address' => null,
                        'device_id' => null,
                    ]);

                    $this->warn("  MISMATCH: Wallet {$wallet->id} (User: {$wallet->user_id}) — Local: ₦{$localBalance} | Monnify: ₦{$monnifyBalance} | Diff: ₦{$difference}");
                }
            } catch (\Throwable $throwable) {
                $skipped++;

                Log::channel('monnify')->error('Balance reconciliation check failed for wallet', [
                    'wallet_id' => $wallet->id,
                    'mmo_wallet_id' => $wallet->mmo_wallet_id,
                    'error' => $throwable->getMessage(),
                ]);

                $this->warn("  SKIP: Wallet {$wallet->id} — {$throwable->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Reconciliation complete. Checked: {$checked} | Mismatches: {$mismatches} | Skipped: {$skipped}");

        if ($mismatches > 0) {
            $this->warn("{$mismatches} balance mismatch(es) flagged to audit_logs for manual investigation.");

            return Command::SUCCESS;
        }

        return Command::SUCCESS;
    }
}
