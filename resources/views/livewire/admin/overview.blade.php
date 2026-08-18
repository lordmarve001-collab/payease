<div class="px-4 py-6 md:p-8 w-full max-w-7xl mx-auto space-y-6">
    
    <div class="mb-2">
        <h1 class="text-2xl font-bold text-text-primary">Dashboard Overview</h1>
        <p class="text-text-secondary text-sm">Welcome back, {{ $adminUser->full_name }}. Here's what's happening today.</p>
    </div>

    <!-- KPI Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
        <x-stat-card
            label="Total Users"
            :value="$kpis['total_users']['value']"
            :trend="$kpis['total_users']['trend']"
            :trend-direction="$kpis['total_users']['direction']"
        />
        <x-stat-card
            label="Daily Transactions"
            :value="$kpis['daily_transactions']['value']"
            :trend="$kpis['daily_transactions']['trend']"
            :trend-direction="$kpis['daily_transactions']['direction']"
        />
        <x-stat-card
            label="Revenue"
            :value="$kpis['revenue']['value']"
            :trend="$kpis['revenue']['trend']"
            :trend-direction="$kpis['revenue']['direction']"
        />
        <x-stat-card
            label="Active Agents"
            :value="$kpis['active_agents']['value']"
            :trend="$kpis['active_agents']['trend']"
            :trend-direction="$kpis['active_agents']['direction']"
        />
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
        
        <!-- Transaction Volume Chart -->
        <x-chart-card title="Transaction Volume (7 Days)" id="txVolumeChart">
            <script>
                document.addEventListener('livewire:initialized', () => {
                    const ctx = document.getElementById('txVolumeChart').getContext('2d');
                    const isDark = document.documentElement.classList.contains('dark');
                    const gridColor = isDark ? '#334155' : '#E5E7EB';
                    const textColor = isDark ? '#94A3B8' : '#6B7280';
                    const labels = @json($transactionVolumeChart['labels']);
                    const data = @json($transactionVolumeChart['data']);
                    
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Volume',
                                data,
                                borderColor: '#00A86B',
                                backgroundColor: 'rgba(0, 168, 107, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#00A86B'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } },
                                x: { grid: { display: false }, ticks: { color: textColor } }
                            }
                        }
                    });
                });
            </script>
        </x-chart-card>

        <!-- Agent Performance Chart -->
        <x-chart-card title="Top Agents by Volume" id="agentPerfChart">
            <script>
                document.addEventListener('livewire:initialized', () => {
                    const ctx = document.getElementById('agentPerfChart').getContext('2d');
                    const isDark = document.documentElement.classList.contains('dark');
                    const gridColor = isDark ? '#334155' : '#E5E7EB';
                    const textColor = isDark ? '#94A3B8' : '#6B7280';
                    const labels = @json($agentPerformanceChart['labels']);
                    const data = @json($agentPerformanceChart['data']);

                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Transactions',
                                data,
                                backgroundColor: ['#00A86B', '#1A73E8', '#008F5A', '#FF6B35', '#DC3545'],
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } },
                                x: { grid: { display: false }, ticks: { color: textColor } }
                            }
                        }
                    });
                });
            </script>
        </x-chart-card>

    </div>

    <!-- Widgets Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
        
        <!-- Recent Alerts -->
        <div class="lg:col-span-2 bg-surface rounded-card shadow-elevation-1 border border-border overflow-hidden">
            <div class="p-4 border-b border-border bg-background/50 flex justify-between items-center">
                <h3 class="font-bold text-text-primary text-base">Recent Alerts</h3>
            </div>
            <div class="divide-y divide-border">
                @foreach($alerts as $alert)
                    @php
                        $icon = match ($alert['severity']) {
                            'critical' => 'alert-octagon',
                            'warning' => 'alert-triangle',
                            default => 'info',
                        };
                        $titleClass = match ($alert['severity']) {
                            'critical' => 'text-danger',
                            'warning' => 'text-orange-600 dark:text-orange-400',
                            default => 'text-secondary',
                        };
                    @endphp
                    <div class="p-4 flex items-start gap-3 hover:bg-background transition-colors">
                        <div class="mt-0.5">
                            @if($icon === 'alert-octagon')
                                <x-lucide-alert-octagon class="w-5 h-5 text-danger" />
                            @elseif($icon === 'alert-triangle')
                                <x-lucide-alert-triangle class="w-5 h-5 text-orange-500" />
                            @else
                                <x-lucide-info class="w-5 h-5 text-secondary" />
                            @endif
                        </div>
                        <div>
                            <p class="text-sm font-semibold {{ $titleClass }}">{{ $alert['title'] }}</p>
                            <p class="text-xs text-text-secondary mt-0.5">{{ $alert['message'] }}</p>
                        </div>
                        <span class="text-xs text-text-secondary ml-auto whitespace-nowrap">Live</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Pending KYC -->
        <div class="bg-surface rounded-card shadow-elevation-1 border border-border flex flex-col">
            <div class="p-4 border-b border-border bg-background/50">
                <h3 class="font-bold text-text-primary text-base">KYC Queue</h3>
            </div>
            <div class="p-6 flex-1 flex flex-col justify-center">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <p class="text-3xl font-bold text-text-primary tabular-nums">{{ $kycCounts['pending'] }}</p>
                        <p class="text-sm font-medium text-orange-600 dark:text-orange-400">Pending Reviews</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center text-orange-600">
                        <x-lucide-clock class="w-6 h-6" />
                    </div>
                </div>
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <p class="text-2xl font-bold text-text-primary tabular-nums">{{ $kycCounts['rejected'] }}</p>
                        <p class="text-sm font-medium text-danger">Rejected (Needs follow-up)</p>
                    </div>
                </div>
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <p class="text-2xl font-bold text-text-primary tabular-nums">{{ $kycCounts['verified'] }}</p>
                        <p class="text-sm font-medium text-primary">Verified</p>
                    </div>
                </div>
                <a href="{{ url('/admin/kyc-queue') }}" wire:navigate class="mt-auto block w-full text-center py-2 bg-primary-light text-primary font-bold rounded-btn hover:bg-primary/20 transition-colors">
                    Process Queue
                </a>
            </div>
        </div>

    </div>

</div>
