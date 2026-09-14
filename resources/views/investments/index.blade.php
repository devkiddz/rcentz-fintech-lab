<x-user-layout>
    <x-slot name="header">Investments</x-slot>

    <div class="ui-page">
        <section class="ui-page-header">
            <div>
                <p class="ui-kicker">Investments</p>
                <h1 class="ui-heading">Investment plans</h1>
                <p class="ui-lead">Explore professionally structured portfolios across different goals and risk levels.</p>
            </div>
            <div class="ui-header-actions">
                <a href="{{ route('portfolio.index') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="briefcase-business" class="w-4 h-4"></i>
                    My portfolio
                </a>
            </div>
        </section>

        <section class="ui-metric-grid ui-metric-grid-3">
            <article class="ui-metric-card">
                <div class="ui-metric-icon"><i data-lucide="layers-3" class="w-4 h-4"></i></div>
                <div>
                    <span>Available plans</span>
                    <strong>{{ $plans->total() }}</strong>
                    <small>Active opportunities</small>
                </div>
            </article>
            <article class="ui-metric-card">
                <div class="ui-metric-icon"><i data-lucide="layout-grid" class="w-4 h-4"></i></div>
                <div>
                    <span>Categories</span>
                    <strong>{{ $categories->count() }}</strong>
                    <small>Diversified choices</small>
                </div>
            </article>
            <article class="ui-metric-card">
                <div class="ui-metric-icon"><i data-lucide="sparkles" class="w-4 h-4"></i></div>
                <div>
                    <span>Featured</span>
                    <strong>{{ $featuredPlans->total() }}</strong>
                    <small>Highlighted plans</small>
                </div>
            </article>
        </section>

        <section class="ui-panel ui-filter-panel">
            <form method="GET" class="ui-filter-form">
                <div class="ui-field ui-field-wide">
                    <label for="search">Search plans</label>
                    <div class="ui-input-wrap">
                        <i data-lucide="search" class="w-4 h-4"></i>
                        <input id="search" name="search" type="text" value="{{ request('search') }}" placeholder="Search by name, category or description">
                    </div>
                </div>
                <div class="ui-field">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ui-field">
                    <label for="risk_level">Risk level</label>
                    <select id="risk_level" name="risk_level">
                        <option value="">All risk levels</option>
                        <option value="conservative" {{ request('risk_level') == 'conservative' ? 'selected' : '' }}>Conservative</option>
                        <option value="moderate" {{ request('risk_level') == 'moderate' ? 'selected' : '' }}>Moderate</option>
                        <option value="aggressive" {{ request('risk_level') == 'aggressive' ? 'selected' : '' }}>Aggressive</option>
                    </select>
                </div>
                <div class="ui-field">
                    <label for="sort">Sort by</label>
                    <select id="sort" name="sort">
                        <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name A-Z</option>
                        <option value="performance" {{ request('sort') == 'performance' ? 'selected' : '' }}>Best performance</option>
                        <option value="nav" {{ request('sort') == 'nav' ? 'selected' : '' }}>Lowest NAV</option>
                        <option value="risk" {{ request('sort') == 'risk' ? 'selected' : '' }}>Risk level</option>
                    </select>
                </div>
                <div class="ui-filter-actions">
                    <button type="submit" class="ui-btn ui-btn-primary">
                        <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                        Apply filters
                    </button>
                    <a href="{{ route('investments.index') }}" class="ui-btn ui-btn-ghost">Reset</a>
                </div>
            </form>
        </section>

        @if($featuredPlans->count() > 0)
            <section class="ui-section">
                <div class="ui-section-heading">
                    <div>
                        <p class="ui-kicker">Curated</p>
                        <h2>Featured plans</h2>
                    </div>
                    <span class="ui-section-meta">{{ $featuredPlans->total() }} featured</span>
                </div>

                <div class="ui-plan-grid ui-plan-grid-featured">
                    @foreach($featuredPlans as $plan)
                        <article class="ui-plan-card ui-plan-card-featured">
                            <div class="ui-plan-topline">
                                <span class="ui-plan-category">{{ $plan->category }}</span>
                                <span class="ui-risk-badge {{ $plan->risk_level_badge }}">{{ ucfirst($plan->risk_level) }}</span>
                            </div>

                            <div class="ui-plan-title-row">
                                <div>
                                    <h3>{{ $plan->name }}</h3>
                                    <p>Structured portfolio</p>
                                </div>
                                <div class="ui-plan-trend {{ $plan->nav_change_percentage >= 0 ? 'is-positive' : 'is-negative' }}">
                                    <i data-lucide="{{ $plan->nav_change_percentage >= 0 ? 'trending-up' : 'trending-down' }}" class="w-4 h-4"></i>
                                    {{ $plan->nav_change_percentage >= 0 ? '+' : '' }}{{ number_format($plan->nav_change_percentage, 2) }}%
                                </div>
                            </div>

                            <div class="ui-plan-stats">
                                <div>
                                    <span>Current NAV</span>
                                    <strong>{{ currency_symbol() }}{{ number_format($plan->nav, 4) }}</strong>
                                </div>
                                <div>
                                    <span>Minimum</span>
                                    <strong>{{ currency_symbol() }}{{ number_format($plan->minimum_investment, 0) }}</strong>
                                </div>
                            </div>

                            <div class="ui-plan-actions">
                                <a href="{{ route('investments.show', $plan) }}" class="ui-btn ui-btn-ghost">View details</a>
                                <a href="{{ route('investments.buy', $plan) }}" class="ui-btn ui-btn-primary">Invest now</a>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if($featuredPlans->hasPages())
                    <div class="ui-pagination">{{ $featuredPlans->links() }}</div>
                @endif
            </section>
        @endif

        <section class="ui-panel ui-section">
            <div class="ui-section-heading ui-section-heading-compact">
                <div>
                    <h2>All investment plans</h2>
                    <p>{{ $plans->total() }} opportunities available</p>
                </div>
            </div>

            @if($plans->count() > 0)
                <div class="ui-plan-list">
                    @foreach($plans as $plan)
                        <article class="ui-plan-row">
                            <div class="ui-plan-row-main">
                                <div class="ui-plan-avatar"><i data-lucide="chart-no-axes-combined" class="w-4 h-4"></i></div>
                                <div class="ui-plan-copy">
                                    <div class="ui-plan-row-title">
                                        <h3>{{ $plan->name }}</h3>
                                        <span class="ui-risk-badge {{ $plan->risk_level_badge }}">{{ ucfirst($plan->risk_level) }}</span>
                                    </div>
                                    <p>{{ $plan->category }}</p>
                                </div>
                            </div>

                            <div class="ui-plan-row-stat">
                                <span>NAV</span>
                                <strong>{{ currency_symbol() }}{{ number_format($plan->nav, 4) }}</strong>
                            </div>
                            <div class="ui-plan-row-stat">
                                <span>Return</span>
                                <strong class="{{ $plan->nav_change_percentage >= 0 ? 'text-emerald-500' : 'text-red-500' }}">
                                    {{ $plan->nav_change_percentage >= 0 ? '+' : '' }}{{ number_format($plan->nav_change_percentage, 2) }}%
                                </strong>
                            </div>
                            <div class="ui-plan-row-stat">
                                <span>Minimum</span>
                                <strong>{{ currency_symbol() }}{{ number_format($plan->minimum_investment, 0) }}</strong>
                            </div>
                            <div class="ui-plan-row-actions">
                                <a href="{{ route('investments.show', $plan) }}" class="ui-icon-btn" title="View plan"><i data-lucide="arrow-up-right" class="w-4 h-4"></i></a>
                                <a href="{{ route('investments.buy', $plan) }}" class="ui-btn ui-btn-secondary ui-btn-sm">Invest</a>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="ui-pagination">{{ $plans->links() }}</div>
            @else
                <div class="ui-empty-state">
                    <div class="ui-empty-icon"><i data-lucide="search-x" class="w-5 h-5"></i></div>
                    <h3>No investment plans found</h3>
                    <p>Try changing your filters or reset the search.</p>
                    <a href="{{ route('investments.index') }}" class="ui-btn ui-btn-secondary">Reset filters</a>
                </div>
            @endif
        </section>
    </div>
</x-user-layout>
