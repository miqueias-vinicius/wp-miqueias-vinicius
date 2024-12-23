<?php

if (!class_exists('WP_MV_Taxonomy')) {
    class WP_MV_Taxonomy
    {
        private $taxonomy;
        private $object_type;
        private $args;

        public function __construct($taxonomy, $object_type = [], $args = [])
        {
            $this->taxonomy = $taxonomy;
            $this->object_type = $object_type;
            $this->args = $args;

            add_action('init', [$this, 'register']);

            add_action('rest_api_init', [$this, 'register_api_routes']);

            add_action('admin_enqueue_scripts', function () {
                wp_enqueue_media();
            });
        }

        public function register()
        {
            if (!taxonomy_exists($this->taxonomy)) {
                $labels = $this->build_labels();
                $args = $this->build_args($labels);

                register_taxonomy($this->taxonomy, $this->object_type, $args);
            } else {
                $get_taxonomy = get_taxonomy($this->taxonomy);

                if ($get_taxonomy) {
                    $labels = $this->build_labels();

                    foreach ($labels as $key => $label) {
                        if (property_exists($get_taxonomy->labels, $key)) {
                            $get_taxonomy->labels->$key = $label;
                        }
                    }
                }
            }
        }

        public function render_field($name, $field, $value)
        {
            $type = esc_attr($field['type']);
            $label = esc_html($field['label']);
            $value = esc_attr($value);

            echo "<div class='form-field'>";
            echo "<label for='{$name}'>{$label}</label>";

            if ($type === 'text') {
                echo "<input id='{$name}' type='text' name='{$name}' value='{$value}' />";
            } elseif ($type === 'media') {
                $style = $value ? '' : 'display: none;';
                echo "<div id='{$name}_preview' style='margin-top: 10px; {$style}'>";
                if ($value) {
                    echo "<img src='" . esc_url($value) . "' style='width: 131px; height: 131px; object-fit: cover;'>";
                }
                echo "</div>";
                echo "<input type='hidden' id='{$name}' name='{$name}' value='{$value}' />";
                echo "<button type='button' id='{$name}_button' class='button button-primary'>Enviar mídia</button>";
                echo "<button type='button' id='{$name}_remove_button' class='button' style='display: " . ($value ? 'inline' : 'none') . ";'>Excluir</button>";

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
                                    mediaField.val(attachment.url);
                                    mediaPreview.html(`<img src="${attachment.url}" style="width: 131px;height: 131px;object-fit: cover;">`).show();
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
            } else {
                echo "<input id='{$name}' type='{$type}' name='{$name}' value='{$value}' />";
            }

            echo "</div>";
        }

        public function add_field($args = [])
        {
            $id = esc_attr($args['id'] ?? '');
            $field = $args;

            add_action("{$this->taxonomy}_add_form_fields", function () use ($field, $id) {
                $this->render_field($id, $field, '');
            });

            add_action("{$this->taxonomy}_edit_form_fields", function ($term) use ($field, $id) {
                $value = get_term_meta($term->term_id, $id, true);
                $this->render_field($id, $field, $value);
            });

            add_action('created_' . $this->taxonomy, function ($term_id) use ($id) {
                if (isset($_POST[$id])) {
                    update_term_meta($term_id, $id, sanitize_text_field($_POST[$id]));
                }
            });

            add_action('edited_' . $this->taxonomy, function ($term_id) use ($id) {
                if (isset($_POST[$id])) {
                    update_term_meta($term_id, $id, sanitize_text_field($_POST[$id]));
                }
            });
        }

        private function build_labels()
        {
            $name = $this->args['name'] ?? ucfirst($this->taxonomy);
            $singular_name = $this->args['singular_name'] ?? $name;

            return [
                'name'              => esc_html__($name, WP_MV_TEXT_DOMAIN),
                'singular_name'     => esc_html__($singular_name, WP_MV_TEXT_DOMAIN),
                'search_items'      => sprintf(esc_html__('Procurar %s', WP_MV_TEXT_DOMAIN), strtolower($name)),
                'all_items'         => sprintf(esc_html__('Todos os %s', WP_MV_TEXT_DOMAIN), strtolower($name)),
                'parent_item'       => sprintf(esc_html__('%s pai', WP_MV_TEXT_DOMAIN), $singular_name),
                'parent_item_colon' => sprintf(esc_html__('%s pai:', WP_MV_TEXT_DOMAIN), $singular_name),
                'edit_item'         => sprintf(esc_html__('Editar %s', WP_MV_TEXT_DOMAIN), $singular_name),
                'update_item'       => sprintf(esc_html__('Atualizar %s', WP_MV_TEXT_DOMAIN), $singular_name),
                'add_new_item'      => sprintf(esc_html__('Adicionar novo %s', WP_MV_TEXT_DOMAIN), $singular_name),
                'new_item_name'     => sprintf(esc_html__('Novo nome de %s', WP_MV_TEXT_DOMAIN), $singular_name),
                'menu_name'         => esc_html__($name, WP_MV_TEXT_DOMAIN),
            ];
        }

        private function build_args($labels)
        {
            return [
                'labels'            => $labels,
                'hierarchical'      => $this->args['hierarchical'] ?? true,
                'public'            => $this->args['public'] ?? true,
                'show_ui'           => $this->args['show_ui'] ?? true,
                'show_in_menu'      => $this->args['show_in_menu'] ?? true,
                'show_in_nav_menus' => $this->args['show_in_nav_menus'] ?? true,
                'show_tagcloud'     => $this->args['show_tagcloud'] ?? true,
                'show_in_quick_edit' => $this->args['show_in_quick_edit'] ?? true,
                'show_admin_column' => $this->args['show_admin_column'] ?? true,
                'show_in_rest'      => $this->args['show_in_rest'] ?? true,
                'rewrite'           => ['slug' => $this->args['slug'] ?? $this->taxonomy],
            ];
        }

        public function register_api_routes()
        {
            register_rest_route('wp-mv/v1', '/taxonomies/terms/', [
                'methods' => 'POST',
                'callback' => [$this, 'api_add_terms']
            ]);
        }


        public function api_add_terms(WP_REST_Request $request)
        {
            $taxonomy = $request->get_param('taxonomy');
            $term_name = $request->get_param('term_name');
            $description = $request->get_param('description');
            $parent = $request->get_param('parent');

            // Verifica se a taxonomia existe
            if (!taxonomy_exists($taxonomy)) {
                return new WP_REST_Response('Taxonomia não existe.', 400);
            }

            // Se o `term_id` for fornecido, tenta editar o termo existente
            $args = [
                'taxonomy' => $taxonomy,
                'name' => $term_name,
                'description' => $description,
                'parent' => $parent,
            ];

            $term = wp_insert_term($term_name, $taxonomy, $args);

            if (is_wp_error($term)) {
                return new WP_REST_Response('Erro ao criar o termo.', 400);
            }

            return new WP_REST_Response('Termo criado com sucesso.', 201);
        }
    }
}
