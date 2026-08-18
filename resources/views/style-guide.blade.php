<x-layouts.app>
    <!-- Sidebar Preview (hidden on mobile, visible on desktop widths) -->
    <x-sidebar>
        <x-sidebar-item icon="home" label="Dashboard" active="true" />
        <x-sidebar-item icon="send" label="Send Money" />
        <x-sidebar-item icon="history" label="Transactions" />
        <x-sidebar-item icon="credit-card" label="Cards" />
        <x-sidebar-item icon="user" label="Profile" />
    </x-sidebar>

    <!-- Bottom Nav Preview (visible on mobile widths) -->
    <x-bottom-nav>
        <x-bottom-nav-item icon="home" label="Home" active="true" />
        <x-bottom-nav-item icon="history" label="History" />
        <x-bottom-nav-item icon="scan-line" label="Scan" />
        <x-bottom-nav-item icon="credit-card" label="Cards" />
        <x-bottom-nav-item icon="user" label="Profile" />
    </x-bottom-nav>

    <!-- Main Content Area -->
    <div class="md:ml-[72px] lg:ml-[240px] pb-24 md:pb-8 transition-all duration-300">
        
        <!-- Sticky Section-Jump Nav -->
        <div class="sticky top-0 bg-surface/90 backdrop-blur-md border-b border-border z-30 p-4 overflow-x-auto">
            <ul class="flex gap-4 text-sm font-medium whitespace-nowrap">
                <li><a href="#colors" class="text-text-secondary hover:text-primary transition-colors">Color Palette</a></li>
                <li><a href="#typography" class="text-text-secondary hover:text-primary transition-colors">Typography</a></li>
                <li><a href="#buttons" class="text-text-secondary hover:text-primary transition-colors">Buttons</a></li>
                <li><a href="#cards" class="text-text-secondary hover:text-primary transition-colors">Cards</a></li>
                <li><a href="#transactions" class="text-text-secondary hover:text-primary transition-colors">Transactions</a></li>
                <li><a href="#quick-actions" class="text-text-secondary hover:text-primary transition-colors">Quick Actions</a></li>
                <li><a href="#toasts" class="text-text-secondary hover:text-primary transition-colors">Toasts</a></li>
            </ul>
        </div>

        <div class="p-6 max-w-5xl mx-auto space-y-16">
            <header class="mb-12">
                <h1 class="text-4xl font-bold text-text-primary mb-2">Design System</h1>
                <p class="text-text-secondary">PayEase visual style guide & component library</p>
            </header>

            <!-- Colors -->
            <section id="colors" class="scroll-mt-24">
                <h2 class="text-2xl font-bold mb-6 pb-2 border-b border-border">Color Palette</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <div class="h-24 bg-primary rounded-card shadow-sm mb-2"></div>
                        <div class="font-medium">Primary</div>
                        <div class="text-sm text-text-secondary">#00A86B</div>
                    </div>
                    <div>
                        <div class="h-24 bg-primary-dark rounded-card shadow-sm mb-2"></div>
                        <div class="font-medium">Primary Dark</div>
                        <div class="text-sm text-text-secondary">#008F5A</div>
                    </div>
                    <div>
                        <div class="h-24 bg-primary-light rounded-card shadow-sm mb-2 border border-border"></div>
                        <div class="font-medium">Primary Light</div>
                        <div class="text-sm text-text-secondary">#E6F7F0</div>
                    </div>
                    <div>
                        <div class="h-24 bg-secondary rounded-card shadow-sm mb-2"></div>
                        <div class="font-medium">Secondary</div>
                        <div class="text-sm text-text-secondary">#1A73E8</div>
                    </div>
                    <div>
                        <div class="h-24 bg-accent rounded-card shadow-sm mb-2"></div>
                        <div class="font-medium">Accent</div>
                        <div class="text-sm text-text-secondary">#FF6B35</div>
                    </div>
                    <div>
                        <div class="h-24 bg-danger rounded-card shadow-sm mb-2"></div>
                        <div class="font-medium">Danger</div>
                        <div class="text-sm text-text-secondary">#DC3545</div>
                    </div>
                    <div>
                        <div class="h-24 bg-surface rounded-card shadow-sm mb-2 border border-border"></div>
                        <div class="font-medium">Surface</div>
                        <div class="text-sm text-text-secondary">#FFFFFF</div>
                    </div>
                    <div>
                        <div class="h-24 bg-background rounded-card shadow-sm mb-2 border border-border"></div>
                        <div class="font-medium">Background</div>
                        <div class="text-sm text-text-secondary">#F7F8FA</div>
                    </div>
                    <div>
                        <div class="h-24 bg-text-primary rounded-card shadow-sm mb-2"></div>
                        <div class="font-medium">Text Primary</div>
                        <div class="text-sm text-text-secondary">#171B1E</div>
                    </div>
                    <div>
                        <div class="h-24 bg-text-secondary rounded-card shadow-sm mb-2"></div>
                        <div class="font-medium">Text Secondary</div>
                        <div class="text-sm text-text-secondary">#6B7280</div>
                    </div>
                    <div>
                        <div class="h-24 bg-border rounded-card shadow-sm mb-2"></div>
                        <div class="font-medium">Border</div>
                        <div class="text-sm text-text-secondary">#E5E7EB</div>
                    </div>
                </div>
            </section>

            <!-- Typography -->
            <section id="typography" class="scroll-mt-24">
                <h2 class="text-2xl font-bold mb-6 pb-2 border-b border-border">Typography Scale</h2>
                <div class="space-y-8 bg-surface p-8 rounded-card shadow-elevation-1">
                    <div>
                        <div class="text-sm text-text-secondary mb-1">Balance display — 48px Bold</div>
                        <div class="text-5xl font-bold">₦12,500.00</div>
                    </div>
                    <div>
                        <div class="text-sm text-text-secondary mb-1">Hero — 36px Bold</div>
                        <div class="text-4xl font-bold">Good morning, John</div>
                    </div>
                    <div>
                        <div class="text-sm text-text-secondary mb-1">Section Title — 24px Bold</div>
                        <div class="text-2xl font-bold">Recent Transactions</div>
                    </div>
                    <div>
                        <div class="text-sm text-text-secondary mb-1">Card Title — 18px SemiBold</div>
                        <div class="text-lg font-semibold">Total Sent</div>
                    </div>
                    <div>
                        <div class="text-sm text-text-secondary mb-1">Body — 16px Regular</div>
                        <div class="text-base">The quick brown fox jumps over the lazy dog.</div>
                    </div>
                    <div>
                        <div class="text-sm text-text-secondary mb-1">Small — 14px Medium</div>
                        <div class="text-sm font-medium">View all activity</div>
                    </div>
                    <div>
                        <div class="text-sm text-text-secondary mb-1">Caption — 12px Regular</div>
                        <div class="text-xs">Jan 12, 2026 • 14:30 PM</div>
                    </div>
                </div>
            </section>

            <!-- Buttons -->
            <section id="buttons" class="scroll-mt-24">
                <h2 class="text-2xl font-bold mb-6 pb-2 border-b border-border">Buttons</h2>
                <div class="space-y-8">
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Default Size</h3>
                        <div class="flex flex-wrap gap-4">
                            <x-button variant="primary">Primary Button</x-button>
                            <x-button variant="secondary">Secondary Button</x-button>
                            <x-button variant="danger">Danger Button</x-button>
                            <x-button variant="primary" disabled>Disabled State</x-button>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Large Size</h3>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <div class="w-full sm:w-1/3">
                                <x-button variant="primary" size="large">Primary Large</x-button>
                            </div>
                            <div class="w-full sm:w-1/3">
                                <x-button variant="secondary" size="large">Secondary Large</x-button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Cards -->
            <section id="cards" class="scroll-mt-24">
                <h2 class="text-2xl font-bold mb-6 pb-2 border-b border-border">Cards</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-balance-card amount="₦12,500.00" account-number="8031234567" />
                    <div class="flex flex-col gap-6">
                        <x-stat-card label="Total Users" value="12,450" trend="15%" trend-direction="up" />
                        <x-stat-card label="Failed Transactions" value="23" trend="5%" trend-direction="down" />
                    </div>
                </div>
            </section>

            <!-- Transaction List -->
            <section id="transactions" class="scroll-mt-24">
                <h2 class="text-2xl font-bold mb-6 pb-2 border-b border-border">Transaction List</h2>
                <div class="max-w-md space-y-3">
                    <x-transaction-item type="credit" title="Salary Deposit" subtitle="Tech Corp Ltd." amount="₦450,000.00" timestamp="Today, 10:23 AM" />
                    <x-transaction-item type="debit" title="Airtime Purchase" subtitle="MTN NGN" amount="₦2,000.00" timestamp="Yesterday, 14:05 PM" />
                    <x-transaction-item type="credit" title="Transfer from Jane" subtitle="Opay / Jane Doe" amount="₦15,000.00" timestamp="Jan 10, 09:15 AM" />
                    <x-transaction-item type="failed" title="DSTV Subscription" subtitle="Insufficient Funds" amount="₦12,500.00" timestamp="Jan 08, 18:42 PM" />
                </div>
            </section>

            <!-- Quick Actions -->
            <section id="quick-actions" class="scroll-mt-24">
                <h2 class="text-2xl font-bold mb-6 pb-2 border-b border-border">Quick Actions Grid</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <x-quick-action icon="send" label="Send Money" />
                    <x-quick-action icon="plus-circle" label="Add Money" />
                    <x-quick-action icon="smartphone" label="Buy Airtime" />
                    <x-quick-action icon="receipt" label="Pay Bills" />
                    <x-quick-action icon="users" label="My Ajo" />
                    <x-quick-action icon="banknote" label="Get Loan" />
                </div>
            </section>

            <!-- Toasts -->
            <section id="toasts" class="scroll-mt-24">
                <h2 class="text-2xl font-bold mb-6 pb-2 border-b border-border">Toasts</h2>
                <div class="flex gap-4">
                    <x-button variant="primary" @click="$dispatch('notify-success', 'Money sent successfully to Jane Doe!')">
                        Trigger Success Toast
                    </x-button>
                    <x-button variant="danger" @click="$dispatch('notify-error', 'Transaction failed. Please try again.')">
                        Trigger Error Toast
                    </x-button>
                </div>
                
                <!-- Toast Components -->
                <x-toast type="success" />
                <x-toast type="error" />
            </section>

        </div>
    </div>
</x-layouts.app>
