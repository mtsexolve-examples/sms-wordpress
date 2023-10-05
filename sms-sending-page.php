<h2> <?php esc_attr_e( 'Отправка SMS-сообщения через Exolve', 'WpAdminStyle' ); ?></h2>
<div class="wrap">
    <div class="metabox-holder columns-2">
        <div class="meta-box-sortables ui-sortable">
            <div class="postbox">
                <h2 class="hndle">
                    <span> <?php esc_attr_e( 'Отправка SMS', 'WpAdminStyle' ); ?></span>
                </h2>
                <div class="inside">
                    <form method="post" name="cleanup_options" action="">
                        <input type="text" name="number" class="regular-text" placeholder="7999XXXXXXX" required /><br><br>
                        <textarea name="message" cols="50" rows="7" placeholder="Сообщение"></textarea><br><br>
                        <input class="button-primary" type="submit" value="Отправить" name="send_sms_message" />
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>