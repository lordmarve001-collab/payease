<div class="p-4 md:p-6 space-y-6">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Pending Disbursements</h1>
            <p class="text-sm text-text-secondary">Transfers awaiting OTP validation before completion.</p>
        </div>
        <div class="rounded-2xl border border-warning/30 bg-warning/10 px-4 py-3 text-sm text-text-primary max-w-lg">
            <strong>Note:</strong> Monnify requires an OTP for each disbursement unless an OTP waiver has been granted with IP whitelisting. Enter the OTP received on your registered disbursement phone/email to release each queued payout.
        </div>
    </div>

    <section class="rounded-card border border-border bg-surface shadow-soft overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border bg-background">
                        <th class="px-4 py-3 text-left font-semibold text-text-secondary">Reference</th>
                        <th class="px-4 py-3 text-left font-semibold text-text-secondary">Sender</th>
                        <th class="px-4 py-3 text-left font-semibold text-text-secondary">Destination</th>
                        <th class="px-4 py-3 text-right font-semibold text-text-secondary">Amount</th>
                        <th class="px-4 py-3 text-left font-semibold text-text-secondary">Created</th>
                        <th class="px-4 py-3 text-right font-semibold text-text-secondary">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingDisbursements as $transaction)
                        @php
                            $destinationAccount = $transaction->metadata['destination_account_number'] ?? '';
                            $destinationName = $transaction->metadata['destination_account_name'] ?? '';
                            $destinationBank = $transaction->metadata['destination_bank_code'] ?? '';
                        @endphp
                        <tr class="border-b border-border last:border-0 hover:bg-background/50 transition-colors">
                            <td class="px-4 py-3 font-mono text-xs text-text-primary">{{ $transaction->reference }}</td>
                            <td class="px-4 py-3 text-text-primary">{{ $transaction->fromWallet?->user?->full_name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <div class="text-text-primary">{{ $destinationName }}</div>
                                <div class="text-xs text-text-secondary">{{ $destinationBank }} - {{ $destinationAccount }}</div>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums text-text-primary">₦{{ number_format($transaction->amount, 2) }}</td>
                            <td class="px-4 py-3 text-text-secondary">{{ $transaction->created_at->diffForHumans() }}</td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    wire:click="openOtpModal('{{ $transaction->id }}')"
                                    class="inline-flex items-center justify-center rounded-btn bg-primary px-4 py-2 text-xs font-semibold text-white transition hover:opacity-90"
                                >
                                    Enter OTP
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-text-secondary">
                                <div class="flex flex-col items-center">
                                    <x-lucide-check-circle class="w-12 h-12 text-success mb-3" />
                                    <h3 class="text-lg font-semibold text-text-primary">All clear</h3>
                                    <p class="text-sm mt-1">No disbursements are waiting for OTP validation.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pendingDisbursements->hasPages())
            <div class="border-t border-border px-4 py-3">
                {{ $pendingDisbursements->links() }}
            </div>
        @endif
    </section>

    @if($showOtpModal && $selectedTransaction)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="closeOtpModal">
            <div class="bg-surface rounded-card shadow-elevation-3 w-full max-w-md mx-4 p-6 space-y-5" @click.stop>
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-text-primary">Validate Disbursement OTP</h3>
                    <button wire:click="closeOtpModal" class="text-text-secondary hover:text-text-primary transition-colors">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <div class="rounded-2xl border border-border bg-background p-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Reference</span>
                        <span class="font-mono text-text-primary">{{ $selectedTransaction->reference }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Amount</span>
                        <span class="font-semibold text-text-primary tabular-nums">₦{{ number_format($selectedTransaction->amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Recipient</span>
                        <span class="text-text-primary">{{ $selectedTransaction->metadata['destination_account_name'] ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Account</span>
                        <span class="font-mono text-text-primary">{{ $selectedTransaction->metadata['destination_account_number'] ?? 'N/A' }}</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">OTP Code</label>
                    <input
                        type="text"
                        wire:model="otpValue"
                        class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm text-center tracking-widest font-mono focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        placeholder="Enter OTP"
                        autofocus
                    >
                    @error('otpValue') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3">
                    <button
                        wire:click="closeOtpModal"
                        class="flex-1 inline-flex items-center justify-center rounded-btn border border-border px-4 py-3 text-sm font-semibold text-text-primary transition hover:border-primary hover:text-primary"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="submitOtp"
                        wire:loading.attr="disabled"
                        class="flex-1 inline-flex items-center justify-center rounded-btn bg-primary px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="submitOtp">Submit OTP</span>
                        <span wire:loading wire:target="submitOtp">Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
