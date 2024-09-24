<div class="panel-kangu-tracking">
<div><h2><?php echo __('Rastreio do pedido', 'rpship') ?></h2></div>
    <div class="box">
        <div><h3><?php echo __('Última ocorrência', 'rpship') ?></h3></div>
        <div><b><?php echo __('Status:', 'rpship') ?></b> <?php echo esc_html($situacao['ocorrencia']); ?></div>
        <div><b><?php echo __('Data:', 'rpship') ?></b> <?php echo date('d/m/Y', strtotime(esc_html($situacao['data']))); ?></div>
        <div><?php echo esc_html($situacao['observacao']); ?></div>
    </div>
    <?php if (count($historico) > 1): ?>
    <div class="box-historics">
        <div><h3><?php echo __('Histórico de ocorrências', 'rpship') ?></h3></div>
        <?php foreach ($historico as $historic): ?>
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
    .panel-kangu-tracking {
        margin: 60px 0;
    }

    .panel-kangu-tracking .box-historics {
        border-top: 1px solid #eee;
        margin-top: 20px;
        max-height: 310px;
        overflow: auto;
        padding-top: 10px;
    }

    .panel-kangu-tracking .historic {
        margin-top: 10px;
    }
</style>