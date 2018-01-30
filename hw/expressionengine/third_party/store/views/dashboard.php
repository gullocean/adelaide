<h3><?= lang('store.dashboard.title') ?></h3>
<div id="store_dashboard_graph"></div>

<div class="store_dashboard_stats">
    <div class="cell">
        <div class="title"><?= lang('store.revenue') ?></div>
        <div class="value"><?= store_currency($stats['revenue']) ?></div>
        <div class="change"><?= store_format_indicator($stats['revenue'], $stats['prev_revenue']) ?></div>
    </div>
    <div class="cell">
        <div class="title"><?= lang('store.orders') ?></div>
        <div class="value"><?= $stats['orders'] ?></div>
        <div class="change"><?= store_format_indicator($stats['orders'], $stats['prev_orders']) ?></div>
    </div>
    <div class="cell">
        <div class="title"><?= lang('store.dashboard.products_sold') ?></div>
        <div class="value"><?= $stats['items'] ?></div>
        <div class="change"><?= store_format_indicator($stats['items'], $stats['prev_items']) ?></div>
    </div>
    <div class="cell">
        <div class="title"><?= lang('store.dashboard.average_order') ?></div>
        <div class="value"><?= store_currency($stats['average_order']) ?></div>
        <div class="change"><?= store_format_indicator($stats['average_order'], $stats['prev_average_order']) ?></div>
    </div>
</div>
