<?php

/*
Plugin Name: SMS
Plugin URI: http://sms-wordpress.local/
Description: Плагин для отправки SMS сообщений с помощью Exolve
Version:  1.0.0
Author: Anastasia Ivanova
*/

class Sms
{
    public $pluginName = "sms";

    /** Функция отображения страницы настроек */
    public function displaySmsSettingsPage()
    {
        include_once "sms-settings-page.php";
    }
    
    public function addSmsOption()
    {
        add_options_page(
            "SMS EXOLVE PAGE", // $page_title
            "SMS EXOLVE", // $menu_title
            "manage_options", // $capability
            $this->pluginName, // $menu_slug
            [$this, "displaySmsSettingsPage"] // $function
        );
    }

    /** Поля для API-ключа и номера */

    public function smsSettingsSave()
    {
        register_setting(
            $this->pluginName,
            $this->pluginName,
            [$this, "pluginOptionsValidate"]
        );
        add_settings_section(
            "sms_main",
            "Настройки",
            [$this, "smsSectionText"],
            "sms-settings-page"
        );
        add_settings_field(
            "exolve_number",
            "Номер Exolve",
            [$this, "smsSettingNumber"],
            "sms-settings-page",
            "sms_main"
        );
        add_settings_field(
            "api_key",
            "API-ключ",
            [$this, "smsSettingKey"],
            "sms-settings-page",
            "sms_main"
        );
    }

    /** Подзаголовок страницы настроек*/
     
    public function smsSectionText()
    {
        echo '<h2">Введите номер Exolve и API-ключ</h2>';
    }

    /** Поле для номера Exolve*/

    public function smsSettingNumber()
    {
        $options = get_option($this->pluginName);
        echo "
            <input
                id='$this->pluginName[exolve_number]'
                name='$this->pluginName[exolve_number]'
                size='40'
                type='text'
                value='{$options['exolve_number']}'
                placeholder='Введите номер Exolve'
            />
        ";
    }

    /** Поле для API-ключа */

    public function smsSettingKey()
    {
        $options = get_option($this->pluginName);
        echo "
            <input
                id='$this->pluginName[api_key]'
                name='$this->pluginName[api_key]'
                size='40'
                type='text'
                value='{$options['api_key']}'
                placeholder='Введите API-ключ приложения'
            />
        ";
    }

    /** Очистить поля для ввода*/

    public function pluginOptionsValidate($input)
    {   
        $newinput["exolve_number"] = trim($input["exolve_number"]);
        $newinput["api_key"] = trim($input["api_key"]);
        return $newinput;
    }

    /** Отображение страницы отправки SMS-сообщения */
    public function displaySendingSmsPage()
    {
        include_once "sms-sending-page.php";
    }

    /** Функция добавления страницы отправки SMS-сообщения */
    public function addSendingSmsPage()
    {
        add_submenu_page(
            "tools.php", // добавляем страницу в меню tools
            __("SENDING SMS PAGE", $this->pluginName . "-sms"), // заголовок страницы
            __("SENDING SMS", $this->pluginName . "-sms"), // заголовок в меню
            "manage_options",
            $this->pluginName . "-sms", 
            [$this, "displaySendingSmsPage"] // вызов функции отображения страницы
        );
    }

    /** Функция отправки SMS-сообщения */
    public function send_message()
    {   
        // POST запрос из формы с номером получателя SMS и текстом сообщения
        if (!isset($_POST["send_sms_message"])) {
            return;
        }

        $to        = (isset($_POST["number"])) ? $_POST["number"] : "";
        $message   = (isset($_POST["message"])) ? $_POST["message"] : "";

        // получение номера Exolve и API-ключа из базы
        $sms_settings = get_option($this->pluginName);
        if (is_array($sms_settings) and count($sms_settings) != 0) {
            $EXOLVE_NUMBER = $sms_settings["exolve_number"];
            $EXOLVE_KEY = $sms_settings["api_key"];
        }

            // отправка HTTP POST запроса в Exolve API
            $url = "https://api.exolve.ru/messaging/v1/SendSMS";

            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            
            $headers = array(
               "Accept: application/json",
               "Authorization: Bearer $EXOLVE_KEY",
               "Content-Type: application/json",
            );
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            
            $data = <<<DATA
            {
                "number":"$EXOLVE_NUMBER", 
                "destination":"$to", 
                "text":"$message"
            } 
            DATA;
            
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            
            // получение ответа от Exolve
            $resp = curl_exec($curl);
            curl_close($curl);
            $decoded_resp = json_decode($resp, true);

            // проверка, было ли сообщение отправлено
            if (array_key_exists("message_id", $decoded_resp)) {
                self::DisplaySuccess();
            } else {
                $error = $decoded_resp["error"]["message"];
                self::DisplayError("Ошибка отправки SMS-сообщения: $error");
            }
    }

    /** Дизайн админ уведомлений (успешная/не успешная отправка) */
    public static function adminNotice($message, $status = true) {
        $class =  ($status) ? "notice notice-success" : "notice notice-error";
        $message = __( $message, "sample-text-domain" );
        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
    }

    /** Ошибка при отправке SMS-сообщения */
    public static function DisplayError($message = "Ошибка отправки SMS-сообщения") {
        add_action( 'admin_notices', function() use($message) {
            self::adminNotice($message, false);
        });
    }

    /** SMS-сообщения успешно отправлено*/
    public static function DisplaySuccess($message = "SMS-сообщение успешно отправлено!") {
        add_action( 'admin_notices', function() use($message) {
            self::adminNotice($message, true);
        });
    }
}

/** Новый инстанс */
$smsInstance = new Sms();

/** Добавление страницы настроек в админ меню */
add_action("admin_menu", [$smsInstance , "addSmsOption"]);

/**Сохранение настроек */ 
add_action("admin_init", [$smsInstance , 'smsSettingsSave']);

/** Добавление страницы отправики SMS-сообщения */
add_action("admin_menu", [$smsInstance , "addSendingSmsPage"]);

/** Запуск функции отправки SMS-сообщения */
add_action( 'admin_init', [$smsInstance , "send_message"] );
    