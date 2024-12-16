<?php

if (!class_exists('WP_MV_Metabox')) {
    class WP_MV_Metabox
    {
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
            wp_enqueue_style(
                "wp-mv-metaboxs",
                WP_MV_METABOXS_URL . "/css/style.css"
            );

            wp_enqueue_style(
                "wp-mv-metaboxs-icons",
                "https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined"
            );

            wp_enqueue_style(
                "wp-mv-metaboxs-selectize",
                "https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/css/selectize.default.min.css"
            );

            wp_enqueue_script(
                "wp-mv-metaboxs-jquery",
                "https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"
            );

            wp_enqueue_script(
                "wp-mv-metaboxs-selectize",
                "https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/js/selectize.min.js"
            );

            wp_enqueue_script(
                "wp-mv-metaboxs",
                WP_MV_METABOXS_URL . "/js/script.js"
            );
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
                <ul>
                    <?php foreach ($metaboxs as $metabox => $attr): ?>
                        <li>
                            <a href="#<?php echo esc_attr($metabox); ?>">
                                <span class="material-symbols-outlined"><?php echo $attr['icon']; ?></span>
                                <?php echo esc_html($attr["label"]); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php foreach ($metaboxs as $metabox => $attr): ?>
                    <div id="<?php echo esc_attr($metabox); ?>">
                        <?php foreach ($attr["items"] as $name => $field): ?>
                            <?php $value = get_post_meta($post->ID, $name, true); ?>
                            <?php $this->render_field($name, $field, $value); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php
        }

        private function render_field($name, $field, $value)
        {

            $mask = isset($field["mask"]) ? "aria-mask='{$field["mask"]}'" : "";

            switch ($field["type"]) {
                case "checkbox":
                    $checked = checked($value, '1', false);
                    echo "<div class='group'>";
                    echo "<input type='hidden' name='{$name}' value='0' />";
                    echo "<label for='{$name}'>{$field["label"]}</label>";
                    echo "<input {$mask} type='checkbox' id='{$name}' name='{$name}' value='1' {$checked} />";
                    echo "</div>";
                    break;
                case "textarea":
                    echo "<div class='group'>";
                    echo "<label for='{$name}'>{$field["label"]}</label>";
                    echo "<textarea {$mask} id='{$name}' name='{$name}'>" . esc_textarea($value) . "</textarea>";
                    echo "</div>";
                    break;
                case "editor":
                    echo "<div class='group'>";
                    echo "<label for='{$name}'>{$field["label"]}</label>";
                    $editor_settings = [
                        'textarea_name' => $name,
                        'media_buttons' => false,
                        'textarea_rows' => 10,
                        'teeny'         => true,
                        'quicktags'     => true
                    ];
                    wp_editor($value, $name, $editor_settings);
                    echo "</div>";
                    break;
                case "select":
                    echo "<div class='group'>";
                    echo "<label for='{$name}'>{$field["label"]}</label>";
                    echo "<select {$mask} id='{$name}' name='{$name}'>";
                    echo "<option value='no_set'>Selecione uma opção</option>";

                    foreach ($field['options'] as $option_value => $option_label) {
                        $selected = selected($value, $option_value, false);
                        echo "<option value='{$option_value}' {$selected}>{$option_label}</option>";
                    }

                    echo "</select>";
                    echo "</div>";
                    echo "<script>
                    jQuery(document).ready(function($) {
                        $('#{$name}').selectize({
                            maxItems: 1,
                            valueField: 'value',
                            labelField: 'text',
                            searchField: 'text',
                            create: false
                        });
                    });
                </script>";
                    break;
                case "media":
                    echo "<div class='group'>";
                    echo "<label for='{$name}'>{$field['label']}</label>";
                    echo "<div>";
                    echo "<div id='{$name}_preview' style='margin-top: 10px; " . ($value ? '' : 'display: none;') . "'>";
                    if ($value) {
                        $file_extension = pathinfo($value, PATHINFO_EXTENSION);
                        $video_extensions = ['mp4', 'mov', 'avi', 'mkv', 'flv'];

                        if (in_array(strtolower($file_extension), $video_extensions)) {
                            echo "<video width='131' height='131' controls>
                                        <source src='" . esc_url($value) . "' type='video/" . esc_attr($file_extension) . "'>
                                        Seu navegador não suporta o elemento de vídeo.
                                      </video>";
                        } else {
                            echo "<img src='" . esc_url($value) . "' style='width: 131px; height: 131px; object-fit: cover;'>";
                        }
                    }
                    echo "</div>";
                    echo "<input type='hidden' id='{$name}' name='{$name}' value='" . esc_attr($value) . "'>";
                    echo "<button type='button' id='{$name}_button' class='button button-primary'>Enviar mídia</button>";
                    echo "<button type='button' id='{$name}_remove_button' class='button' style='display: " . ($value ? 'inline' : 'none') . ";'>Excluir</button>";
                    echo "</div>";
                    echo "</div>";
            ?>
                    <script>
                        (function($) {
                            $(document).ready(function() {
                                const mediaField = $('#<?php echo $name; ?>');
                                const mediaPreview = $('#<?php echo $name; ?>_preview');
                                const uploadButton = $('#<?php echo $name; ?>_button');
                                const removeButton = $('#<?php echo $name; ?>_remove_button');

                                uploadButton.on('click', function(e) {
                                    e.preventDefault();
                                    const mediaUploader = wp.media({
                                        title: 'Selecione uma mídia',
                                        button: {
                                            text: 'Selecionar'
                                        },
                                        multiple: false
                                    }).on('select', function() {
                                        const attachment = mediaUploader.state().get('selection').first().toJSON();
                                        const url = attachment.url;
                                        const file_extension = url.split('.').pop().toLowerCase();
                                        mediaField.val(url);

                                        if (['mp4', 'mov', 'avi', 'mkv', 'flv'].includes(file_extension)) {
                                            mediaPreview.html(`<video width='131' height='131' controls>
                                                                <source src="${url}" type="video/${file_extension}">
                                                                Seu navegador não suporta o elemento de vídeo.
                                                              </video>`).show();
                                        } else {
                                            mediaPreview.html(`<img src="${url}" style="width: 131px; height: 131px; object-fit: cover;">`).show();
                                        }
                                        removeButton.show();
                                    }).open();
                                });

                                removeButton.on('click', function(e) {
                                    e.preventDefault();
                                    mediaField.val('');
                                    mediaPreview.hide();
                                    removeButton.hide();
                                });
                            });
                        })(jQuery);
                    </script>
<?php
                    break;

                case "post_type":
                    if (!empty($field['options']['post_type'])) {

                        $posts = new WP_Query(array(
                            'post_type' => $field['options']['post_type'],
                            'posts_per_page' => -1
                        ));

                        if (!is_wp_error($posts) && $posts->have_posts()) {
                            echo "<div class='group'>";
                            echo "<label for='{$name}'>{$field["label"]}</label>";
                            echo "<select {$mask} id='{$name}' name='{$name}' class='selectize'>";
                            echo "<option value='no_set'>Selecione uma opção</option>";

                            while ($posts->have_posts()) {
                                $posts->the_post();

                                $id = get_the_ID();
                                $title = get_the_title();

                                $selected = selected($value, $id, false);
                                echo "<option value='{$id}' {$selected}>{$title}</option>";
                            }
                            wp_reset_postdata();

                            echo "</select>";
                            echo "</div>";

                            echo "<script>
                                    jQuery(document).ready(function($) {
                                        $('#{$name}').selectize({
                                            maxItems: 1,
                                            valueField: 'value',
                                            labelField: 'text',
                                            searchField: 'text',
                                            create: false
                                        });
                                    });
                                </script>";
                        } else {
                            echo "<div class='group'>";
                            echo "<label>{$field["label"]}</label>";
                            echo "<em>Não foram encontrados posts para '{$field['options']['post_type']}'</em>";
                            echo "</div>";
                        }
                    } else {
                        echo "<div class='group'>";
                        echo "<label>{$field["label"]}</label>";
                        echo "<em>O tipo de post não foi especificada no campo.</em>";
                        echo "</div>";
                    }
                    break;
                case "taxonomy":
                    if (!empty($field['options']['taxonomy'])) {
                        $terms = get_terms([
                            'taxonomy' => $field['options']['taxonomy'],
                            'hide_empty' => false,
                        ]);

                        if (!is_wp_error($terms) && !empty($terms)) {
                            echo "<div class='group'>";
                            echo "<label for='{$name}'>{$field["label"]}</label>";
                            echo "<select {$mask} id='{$name}' name='{$name}'>";
                            echo "<option value='no_set'>Selecione uma opção</option>";

                            foreach ($terms as $term) {
                                $selected = selected($value, $term->term_id, false);
                                echo "<option value='{$term->term_id}' {$selected}>{$term->name}</option>";
                            }

                            echo "</select>";
                            echo "</div>";

                            echo "<script>
                            jQuery(document).ready(function($) {
                                $('#{$name}').selectize({
                                    maxItems: 1,
                                    valueField: 'value',
                                    labelField: 'text',
                                    searchField: 'text',
                                    create: false
                                });
                            });
                        </script>";
                        } else {
                            echo "<div class='group'>";
                            echo "<label>{$field["label"]}</label>";
                            echo "<em>Não foram encontrados termos para a taxonomia '{$field['options']['taxonomy']}'</em>";
                            echo "</div>";
                        }
                    } else {
                        echo "<div class='group'>";
                        echo "<label>{$field["label"]}</label>";
                        echo "<em>A taxonomia não foi especificada no campo.</em>";
                        echo "</div>";
                    }
                    break;
                case "users":
                    if (!empty($field['options']['role'])) {

                        $args = array(
                            'role__in'    => $field['options']['role'],
                            'orderby' => 'display_name',
                            'order'   => 'ASC',
                        );

                        $users = get_users($args);

                        if (!empty($users)) {
                            echo "<div class='group'>";
                            echo "<label for='{$name}'>{$field["label"]}</label>";
                            echo "<select {$mask} id='{$name}' name='{$name}' class='selectize'>";
                            echo "<option value='no_set'>Selecione um usuário</option>";

                            foreach ($users as $user) {
                                $id = $user->ID;
                                $display_name = $user->display_name;

                                $selected = selected($value, $id, false);
                                echo "<option value='{$id}' {$selected}>{$display_name}</option>";
                            }

                            echo "</select>";
                            echo "</div>";

                            echo "<script>
                                        jQuery(document).ready(function($) {
                                            $('#{$name}').selectize({
                                                maxItems: 1,
                                                valueField: 'value',
                                                labelField: 'text',
                                                searchField: 'text',
                                                create: false
                                            });
                                        });
                                    </script>";
                        } else {
                            echo "<div class='group'>";
                            echo "<label>{$field["label"]}</label>";
                            echo "<em>Não foram encontrados usuários com o papel '{$field['options']['role']}'</em>";
                            echo "</div>";
                        }
                    } else {
                        echo "<div class='group'>";
                        echo "<label>{$field["label"]}</label>";
                        echo "<em>O papel do usuário não foi especificado no campo.</em>";
                        echo "</div>";
                    }
                    break;

                default:
                    echo "<div class='group'>";
                    echo "<label for='{$name}'>{$field["label"]}</label>";
                    echo "<input {$mask} id='{$name}' type='{$field["type"]}' name='{$name}' value='" . esc_attr($value) . "' />";
                    echo "</div>";
                    break;
            }
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
                        $sanitize_callback = $field['sanitize_callback'] ?? 'sanitize_text_field';

                        if ($field['type'] === 'editor') {
                            $sanitize_callback = 'wp_kses_post';
                        }

                        $value = call_user_func($sanitize_callback, $_POST[$name]);
                        update_post_meta($post_id, $name, $value);
                    }
                }
            }
        }


        public function register_rest_routes()
        {
            register_rest_route(
                "wp-mv-metaboxs/v1",
                "{$this->post_type}",
                [
                    "methods" => "GET",
                    "callback" => [$this, "api_get_metabox_item_data"],
                ]
            );

            register_rest_route(
                "wp-mv-metaboxs/v1",
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
                return new WP_REST_Response(['error' => 'O ID do post informado é inválido ou não existe'], 403);
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
                return new WP_REST_Response(['error' => 'O ID do post informado é inválido ou não existe'], 403);
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
