<div class="panel-kangu-tracking">
    <div class="box">
        <div><h4><?php echo __('Última ocorrência de rastreio', 'rpship') ?></h4></div>
        <div><b><?php echo __('Status:', 'rpship') ?></b> <?php echo esc_html($situacao['ocorrencia']); ?></div>
        <div><b><?php echo __('Data:', 'rpship') ?></b> <?php echo date('d/m/Y', strtotime(esc_html($situacao['data']))); ?></div>
        <div><?php echo esc_html($situacao['observacao']); ?></div>
    </div>
    <?php if (count($historico) > 1): ?>
    <div class="box-historics">
        <div><h4><?php echo __('Histórico de ocorrências de rastreio', 'rpship') ?></h4></div>
        <?php foreach ($historico as $historic): ?>
        <div class="historic">
            <div><b><?php echo __('Status:', 'rpship') ?></b> <?php echo esc_html($historic['ocorrencia']); ?></div>
            <div><b><?php echo __('Data:', 'rpship') ?></b> <?php echo date('d/m/Y', strtotime(esc_html($historic['data']))); ?></div>
            <div><?php echo esc_html($historic['observacao']); ?></div>
        </div>
        <div class="historic">
            <div><b><?php echo __('Status:', 'rpship') ?></b> <?php echo esc_html($historic['ocorrencia']); ?></div>
            <div><b><?php echo __('Data:', 'rpship') ?></b> <?php echo date('d/m/Y', strtotime(esc_html($historic['data']))); ?></div>
            <div><?php echo esc_html($historic['observacao']); ?></div>
        </div>
        <div class="historic">
            <div><b><?php echo __('Status:', 'rpship') ?></b> <?php echo esc_html($historic['ocorrencia']); ?></div>
            <div><b><?php echo __('Data:', 'rpship') ?></b> <?php echo date('d/m/Y', strtotime(esc_html($historic['data']))); ?></div>
            <div><?php echo esc_html($historic['observacao']); ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
    .panel-kangu-tracking h4 {
        margin: 0.9em 0;
        font-weight: bold;
    }
    .panel-kangu-tracking .box-historics {
        border-top: 1px solid #eee;
        margin-top: 10px;
        max-height: 175px;
        overflow: auto;
    }

    .panel-kangu-tracking .historic {
        margin-top: 10px;
    }

    .kangu-color {
        color: #FF6600
    }
</style>