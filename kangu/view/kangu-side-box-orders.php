<?php if ($has_kangu): ?>
    <div>
        <a href="<?php echo get_admin_url(null, 'admin.php?page=kangu&etiqueta=' .  esc_html((int)$order_id)); ?>" target="_blank" class="button button-primary button-kangu"><?php echo __('Imprimir Etiqueta', 'rpship') ?></a>
    </div>
    <div class="separate">--------------------- OU ---------------------</div>
    <div>
        <a href="https://portal.kangu.com.br/integracoes/cotador/woocommerce" target="_blank" class="button button-primary button-kangu outline"><?php echo __('Comparar Fretes Kangu', 'rpship') ?></a>
    </div>
<?php else: ?>
    <div>
        <a href="https://portal.kangu.com.br/integracoes/cotador/woocommerce" target="_blank" class="button button-primary button-kangu"><?php echo __('Comparar Fretes Kangu', 'rpship') ?></a>
    </div>
    <div class="separate">--------------------- OU ---------------------</div>
    <div>
        <a href="<?php echo get_admin_url(null, 'admin.php?page=kangu&etiqueta=' .  esc_html((int)$order_id)); ?>" target="_blank" class="button button-primary button-kangu outline"><?php echo __('Imprimir Etiqueta', 'rpship') ?></a>
    </div>
<?php endif; ?>
<style>
    .button-kangu, .button-kangu:focus {
        width: 100%;
        text-align: center;
        padding: 5px 10px!important;
        background: #FF6600!important;
        border-color: #FF6600!important;
        box-shadow: none!important;
    }

    .outline, .outline:focus {
        background: #fff!important;
        border-color: #FF6600!important;
        color: #FF6600!important;
    }

    .separate {
        margin: 10px 0;
        text-align: center;
        color: #999;
    }

    .kangu-color {
        color: #FF6600
    }
</style>