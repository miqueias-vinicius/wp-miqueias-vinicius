<?php

// add_action('save_post', function ($post_id) {
//     if (isset($_POST['cliente'])) {
//         error_log(print_r($_POST['cliente'], true)); // Registrar no log para verificar
//     }
// });

if (!class_exists('WP_MV_Metabox')) {
    class WP_MV_Metabox
    {
        /**
         * Registra um metabox com grupos e campos.
         *
         * @param string $id ID único do metabox.
         * @param string $title Título do metabox.
         * @param string $post_type Tipo de post que o metabox será exibido.
         * @param array $groups Cadastro de grupos internos do metabox.
         */

        private $id;
        private $title;
        private $post_type;
        private $groups = [];

        public function __construct($post_type, $args = array())
        {
            $this->id = $args['id'];
            $this->title = $args['title'];
            $this->post_type = $post_type;

            add_action("admin_enqueue_scripts", [$this, "assets"]);
            add_action("add_meta_boxes", [$this, "add_meta_box"]);
            add_action("save_post", [$this, "save_meta_box"]);
            add_action('rest_api_init', [$this, 'register_rest_routes']);
        }

        public function assets()
        {
            wp_enqueue_style("wp-mv-metaboxs", WP_MV_URL . "css/style.css", array(), time(), "all");

            wp_enqueue_script("wp-mv-metaboxs", WP_MV_URL . "js/script.js", array(), time(), array());

            wp_enqueue_style("wp-mv-metaboxs-selectize", WP_MV_URL . "css/selectize.default.min.css", array(), time(), "all");

            wp_enqueue_script("wp-mv-metaboxs-selectize", WP_MV_URL . "js/selectize.min.js", array('jquery'), null, true);

            wp_enqueue_style("wp-mv-metaboxs-icons", "https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined");
        }

        public function add_meta_box()
        {
            add_meta_box(
                $this->id,
                $this->title,
                [$this, "render_metabox"],
                $this->post_type,
                "normal",
                "high"
            );
        }

        /**
         * Registra um grupo dentro do metabox.
         *
         * @param string $id ID do metabox.
         * @param array $args {
         *     Parâmetros para configurar o grupo.
         *
         *     @type string $label Título do grupo. Obrigatório.
         *     @type string $icon  Ícone do grupo. Opcional.
         * }
         */

        public function add_group($id, $args = array())
        {
            $this->groups[$id] = [
                'label' => $args['label'],
                'icon' => $args['icon'] ?? 'edit',
                'items' => []
            ];
        }

        public function add_field($group, $args = array())
        {
            if (!isset($this->groups[$group])) {
                throw new Exception("O grupo '{$group}' não existe.");
            }

            $this->groups[$group]['items'][$args['id']] = [
                'type' => $args['type'],
                'label' => $args['label'],
                'options' => $args['options'] ?? [],
                'mask' => $args['mask'] ?? '',
                'fields' => $args['fields'] ?? [],
                'sanitize_callback' => $args['sanitize_callback'] ?? 'sanitize_text_field',
            ];
        }

        private function metaboxs()
        {
            return $this->groups;
        }

        public function render_metabox($post)
        {
            $metaboxs = $this->metaboxs();

            echo "<style> #{$this->id} .inside { padding: 0; margin: 0; } </style>";
?>
            <div class="wp_mv_metaboxs">
                <ul class="wp_mv_metaboxs__sidebar">
                    <?php foreach ($metaboxs as $metabox => $attr): ?>
                        <li class="wp_mv_metaboxs__sidebar__item" data-tab="#<?php echo esc_attr($metabox); ?>">
                            <a class="wp-mv-tab" href="#">
                                <span class="wp-mv-tab-icon material-symbols-outlined"><?php echo $attr['icon']; ?></span>
                                <div class="wp-mv-tab-info">
                                    <span class="wp-mv-tab-label"><?php echo esc_html($attr["label"]); ?></span>
                                    <span class="wp-mv-tab-description"><?php echo (array_key_exists("description", $attr)) ?? esc_html($attr["description"]); ?></span>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="wp_mv_metaboxs__content">
                    <?php foreach ($metaboxs as $metabox => $attr): ?>
                        <div class="wp_mv_metaboxs__tab" id="<?php echo esc_attr($metabox); ?>">
                            <div class="wp_mv_metaboxs__header">
                                <h2 class="wp_mv_metaboxs__title"><?php echo esc_html($attr["label"]); ?></h2>
                            </div>
                            <div class="wp_mv_metaboxs__items">
                                <?php foreach ($attr["items"] as $name => $field): ?>
                                    <?php $value = get_post_meta($post->ID, $name, true); ?>
                                    <?php $this->render_field($name, $field, $value); ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
<?php
        }

        private function render_field($name, $field, $value)
        {
            echo WP_MV_RenderFields::render_field($name, $field, $value);
        }

        public function save_meta_box($post_id)
        {
            if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
                return;
            }

            if (!current_user_can('edit_post', $post_id)) {
                return;
            }

            $metaboxs = $this->metaboxs();

            foreach ($metaboxs as $metabox) {
                foreach ($metabox['items'] as $name => $field) {
                    if (isset($_POST[$name])) {
                        switch ($field['type']) {
                            case 'multi':
                                if (is_array($_POST[$name])) {
                                    $multi_values = array_map(function ($group) use ($field) {
                                        $sanitized_group = [];
                                        foreach ($field['fields'] as $sub_field) {
                                            $sub_field_id = $sub_field['id'];
                                            $sanitize_sub_callback = $sub_field['sanitize_callback'] ?? 'sanitize_text_field';
                                            if ($sub_field['type'] === 'editor') {
                                                $sanitize_sub_callback = 'wp_kses_post';
                                            }
                                            $sanitized_group[$sub_field_id] = isset($group[$sub_field_id])
                                                ? call_user_func($sanitize_sub_callback, $group[$sub_field_id])
                                                : '';
                                        }
                                        return $sanitized_group;
                                    }, $_POST[$name]);

                                    update_post_meta($post_id, $name, $multi_values);
                                }
                                break;
                            case 'query_posts':
                                if (isset($_POST[$name])) {
                                    update_post_meta($post_id, $name, $_POST[$name]);
                                }
                                break;
                            case 'gallery':
                                if (is_array($_POST[$name])) {
                                    $gallery_values = array_map('esc_url_raw', $_POST[$name]);
                                    update_post_meta($post_id, $name, $gallery_values);
                                }
                                break;
                            case 'editor':
                                $sanitize_callback = function ($content) {
                                    return wp_kses_post(wpautop($content));
                                };

                                $value = call_user_func($sanitize_callback, $_POST[$name]);
                                update_post_meta($post_id, $name, $value);
                                break;
                            default:
                                update_post_meta($post_id, $name, $_POST[$name]);
                                break;
                        }
                    }
                }
            }
        }

        public function register_rest_routes()
        {
            register_rest_route(
                "wp-mv/v1",
                "{$this->post_type}",
                [
                    "methods" => "GET",
                    "callback" => [$this, "api_get_metabox_item_data"],
                ]
            );

            register_rest_route(
                "wp-mv/v1",
                "{$this->post_type}",
                [
                    "methods" => "POST",
                    "callback" => [$this, "api_update_metabox_item_data"],
                ]
            );
        }

        public function api_get_metabox_item_data(WP_REST_Request $request)
        {
            $post_id = $request->get_param('post_id');

            if (!get_post($post_id)) {
                return new WP_REST_Response(['error' => 'O ID do post informado é inválido ou não existe.'], 403);
            }

            $metabox_data = [];
            $metaboxs = $this->metaboxs();

            $metabox_data['id'] = $post_id;
            $metabox_data['title'] = get_the_title($post_id);
            $metabox_data['content'] = apply_filters('the_content', get_post_field('post_content', $post_id));

            foreach ($metaboxs as $group_id => $group) {
                foreach ($group['items'] as $field_id => $field) {
                    $metabox_data[$field_id] = get_post_meta($post_id, $field_id, true);
                }
            }

            return new WP_REST_Response($metabox_data, 200);
        }

        public function api_update_metabox_item_data(WP_REST_Request $request)
        {
            $post_id = $request->get_param('post_id');
            $updated_data = $request->get_json_params();

            if (!get_post($post_id)) {
                return new WP_REST_Response(['error' => 'O ID do post informado é inválido ou não existe.'], 403);
            }

            $metaboxs = $this->metaboxs();

            $metabox_data['id'] = $post_id;
            $metabox_data['title'] = get_the_title($post_id);
            $metabox_data['content'] = apply_filters('the_content', get_post_field('post_content', $post_id));

            foreach ($updated_data as $field_id => $value) {
                foreach ($metaboxs as $group_id => $group) {
                    if (isset($group['items'][$field_id])) {
                        $sanitize_callback = $group['items'][$field_id]['sanitize_callback'] ?? 'sanitize_text_field';
                        $sanitized_value = call_user_func($sanitize_callback, $value);
                        update_post_meta($post_id, $field_id, $sanitized_value);
                    }
                }
            }

            return new WP_REST_Response(['success' => true], 200);
        }
    }
}
