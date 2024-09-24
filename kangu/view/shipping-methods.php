<?php if (!empty($delivery)): ?>
<div class="box-title">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" height="18px">
        <path d="M624 368h-16V251.9c0-19-7.7-37.5-21.1-50.9L503 117.1C489.6 103.7 471 96 452.1 96H416V56c0-30.9-25.1-56-56-56H56C25.1 0 0 25.1 0 56v304c0 30.9 25.1 56 56 56h8c0 53 43 96 96 96s96-43 96-96h128c0 53 43 96 96 96s96-43 96-96h48c8.8 0 16-7.2 16-16v-16c0-8.8-7.2-16-16-16zm-464 96c-26.5 0-48-21.5-48-48s21.5-48 48-48 48 21.5 48 48-21.5 48-48 48zm208-96H242.7c-16.6-28.6-47.2-48-82.7-48s-66.1 19.4-82.7 48H56c-4.4 0-8-3.6-8-8V56c0-4.4 3.6-8 8-8h304c4.4 0 8 3.6 8 8v312zm48-224h36.1c6.3 0 12.5 2.6 17 7l73 73H416v-80zm64 320c-26.5 0-48-21.5-48-48s21.5-48 48-48 48 21.5 48 48-21.5 48-48 48zm80-100.9c-17.2-25.9-46.6-43.1-80-43.1-24.7 0-47 9.6-64 24.9V272h144v91.1z"></path>
    </svg>&nbsp;
    <span><b><?php echo esc_html__( 'Envio a domicílio' ); ?></b></span>
</div>
<div class="box-shipping">
    <ul class="shipping_options_price">
        <?php foreach($delivery as $method): ?>
        <li class="list-readonly">
            <span class="float-left">
                <input name="calc_shipping_method" class="shipping_method" type="hidden" value="<?php echo esc_attr($method['value']); ?>" <?php echo esc_attr($method['checked']); ?> />
                <?php echo wp_kses_post(!empty($method['shipping_label']) ? $method['shipping_label'] : $method['label']); ?> <br>
                <b><?php echo esc_html($method['deadline']); ?></b>
            </span>
            <span class="float-right"> <b><?php echo wc_price($method['cost']); ?></b></span>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if (!empty($pickup)): ?>
<div class="box-title">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" height="20px">
        <path d="M192 0C85.903 0 0 86.014 0 192c0 71.117 23.991 93.341 151.271 297.424 18.785 30.119 62.694 30.083 81.457 0C360.075 285.234 384 263.103 384 192 384 85.903 297.986 0 192 0zm0 464C64.576 259.686 48 246.788 48 192c0-79.529 64.471-144 144-144s144 64.471 144 144c0 54.553-15.166 65.425-144 272zm-80-272c0-44.183 35.817-80 80-80s80 35.817 80 80-35.817 80-80 80-80-35.817-80-80z"></path>
    </svg>
    <span><b><?php echo esc_html__( 'Pontos de retirada Kangu:' ); ?></b></span>
</div>
<div class="box-shipping">
    <ul class="shipping_options_price">
        <li class="list-readonly">
            <span class="float-left">
                <b><?php echo esc_html($pickup[0]['deadline']); ?></b><br>
                <a class="pickups">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" height="14px">
                        <path d="M192 0C85.903 0 0 86.014 0 192c0 71.117 23.991 93.341 151.271 297.424 18.785 30.119 62.694 30.083 81.457 0C360.075 285.234 384 263.103 384 192 384 85.903 297.986 0 192 0zm0 464C64.576 259.686 48 246.788 48 192c0-79.529 64.471-144 144-144s144 64.471 144 144c0 54.553-15.166 65.425-144 272zm-80-272c0-44.183 35.817-80 80-80s80 35.817 80 80-35.817 80-80 80-80-35.817-80-80z"></path>
                    </svg>
                    <span><b><?php echo esc_html__( 'VER PONTOS' ); ?></b></span>
                </a>
            </span>
            <span class="float-right"> <b><?php echo wc_price($pickup[0]['cost']); ?></b></span>
        </li>
    </ul>
</div>
<template id="modal-pickup">
    <swal-title>
        <div class="popup-title">
            <b><?php echo esc_html($pickup[0]['deadline']); ?></b> - <b><?php echo wc_price($pickup[0]['cost']); ?></b> <br />
            <span><?php echo esc_html__( 'Pontos de retira Kangu:' ); ?></span>
        </div>
    </swal-title>
    <swal-html>
        <div class="modal-pickup box-shipping">
            <ul class="shipping_options_price">
                <?php foreach($pickup as $method): ?>
                <li class="list-readonly">
                    <span class="float-left">
                        <input name="calc_shipping_method" class="shipping_method" type="hidden" value="<?php echo esc_attr($method['value']); ?>" <?php echo esc_attr($method['checked']); ?> />
                        <?php echo esc_html($method['point_label']); ?>
                        <br>
                        <?php echo esc_html($method['point_address']); ?>
                        <br>
                        <b><?php echo esc_html($method['distance']); ?></b>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </swal-html>
</template>
<?php endif; ?>

<?php wp_enqueue_script('kangu-sweetalert2-js', self::$plugin_url . 'assets/js/sweetalert2.js'); ?>
<?php wp_enqueue_script('kangu-polyfill-js', self::$plugin_url . 'assets/js/polyfill.min.js'); ?>
<script>
    jQuery(document).on('click', '.pickups', function() {
        Swal.fire({
            template: '#modal-pickup',
            background: '#FAFAFA',
            width: '600px',
            heightAuto: false,
            showCloseButton: true,
            showConfirmButton: false,
            showClass: {
                popup: 'swal2-noanimation',
                backdrop: 'swal2-noanimation'
            }
        });
    });
</script>