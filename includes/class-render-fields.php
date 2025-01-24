<?php

if (!class_exists('WP_MV_RenderFields')) {
    class WP_MV_RenderFields
    {
        /**
         * Renderiza um campo de formulário com base no tipo especificado.
         *
         * @param string $name Título do campo.
         * @param array $field Configurações do campo.
         * @param mixed $value Valor do campo.
         */
        public static function render_field($name, $field, $value)
        {
            $methods = [
                'default' => 'render_default_field',
                'checkbox' => 'render_checkbox_field',
                'select' => 'render_select_field',
                'query_posts' => 'render_query_posts_field',
                'editor' => 'render_editor_field',
                'multi' => 'render_multi_field',
                'media' => 'render_media_field',
                'gallery' => 'render_gallery_field',
                'post_type' => 'render_post_type_field',
                'taxonomy' => 'render_taxonomy_field',
                'users' => 'render_users_field',
            ];

            $type = $field['type'] ?? '';

            if (isset($methods[$type]) && method_exists(__CLASS__, $methods[$type])) {
                return self::{$methods[$type]}($name, $field, $value);
            }

            return self::render_default_field($name, $field, $value);
        }

        private static function render_default_field($name, $field, $value)
        {
            echo "<div class='group'>";
            echo "<label for='{$name}'>{$field["label"]}</label>";
            echo "<input id='{$name}' type='{$field["type"]}' name='{$name}' value='" . esc_attr($value) . "' />";
            echo "</div>";
        }

        private static function render_checkbox_field($name, $field, $value)
        {
            $checked = checked($value, true, false);
            echo "<div class='group'>";
            echo "<label for='{$name}'>";
            echo "<input id='{$name}' type='checkbox' name='{$name}' {$checked} value='1' />";
            echo "{$field['label']}";
            echo "</label>";
            echo "</div>";
        }

        private static function render_select_field($name, $field, $value)
        {
            echo "<div class='group'>";
            echo "<label for='{$name}'>{$field["label"]}</label>";
            echo "<select id='{$name}' name='{$name}'>";
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
        }

        /**
         * Renderiza um campo do tipo query de seleção.
         */
        private static function render_query_posts_field($name, $field, $value)
        {
            global $post;

            $post_id = $post->ID;

            $current_value = get_post_meta($post_id, $name, true);

            if (!is_array($current_value)) {
                $current_value = [];
            }

            if (empty($field['args']) || !isset($field['args'])) {
                echo "<em>Argumentos de query não foram fornecidas.</em>";
                return;
            }

            if (!is_array($field['args'])) {
                echo "<em>Os arguemntos da query precisam ser um array.</em>";
                return;
            }

            $args = $field['args'];
            $query = new WP_Query($args);

            echo "<div class='group'>";
            echo "<label for='{$name}'>{$field['label']}</label>";
            if ($query->have_posts()) {
                echo '<select name="' . esc_attr($name) . '[]" id="' . esc_attr($name) . '" multiple style="width: 100%;">';
                while ($query->have_posts()) {
                    $query->the_post();
                    $selected = in_array(get_the_ID(), $current_value) ? 'selected' : '';
                    echo '<option value="' . esc_attr(get_the_ID()) . '" ' . $selected . '>';
                    echo esc_html(get_the_title(get_the_ID()));
                    echo '</option>';
                }
                echo '</select>';


                echo '<script>
                    jQuery(document).ready(function($) {
                        $("#' . esc_attr($name) . '").selectize({
                            create: false, 
                            sortField: "text",
                            maxItems: null
                        });
                    });
                </script>';
            } else {
                echo "<em>Nenhum post encontrado.</em>";
                return;
            }
            echo "</div>";
        }


        /**
         * Renderiza um campo do tipo editor.
         */
        private static function render_editor_field($name, $field, $value)
        {
            echo "<div class='group'>";
            echo "<label for='{$name}'>{$field["label"]}</label>";

            $editor_id = sanitize_key($name);

            $value = wpautop($value);

            // Configurações do editor
            $editor_settings = [
                'textarea_name' => $name,
                'media_buttons' => false,
                'textarea_rows' => 15,
                'teeny'         => true,
                'quicktags'     => true,
            ];

            wp_editor($value, $editor_id, $editor_settings);
            echo "</div>";
        }

        private static function render_media_field($name, $field, $value)
        {
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
        }

        private static function render_gallery_field($name, $field, $value)
        {
            echo "<div class='group gallery-group'>";
            echo "<label for='{$name}'>{$field['label']}</label>";
            echo "<div id='{$name}_gallery_wrapper' class='gallery-wrapper'>";

            if (!empty($value) && is_array($value)) {
                foreach ($value as $index => $url) {
                    $file_extension = pathinfo($url, PATHINFO_EXTENSION);
                    $video_extensions = ['mp4', 'mov', 'avi', 'mkv', 'flv'];
                    echo "<div class='gallery-item' data-index='{$index}'>";
                    if (in_array(strtolower($file_extension), $video_extensions)) {
                        echo "<video width='131' height='131' controls>
                                            <source src='" . esc_url($url) . "' type='video/" . esc_attr($file_extension) . "'>
                                            Seu navegador não suporta o elemento de vídeo.
                                          </video>";
                    } else {
                        echo "<img src='" . esc_url($url) . "' style='width: 131px; height: 131px; object-fit: cover;'>";
                    }
                    echo "<input type='hidden' name='{$name}[]' value='" . esc_attr($url) . "'>";
                    echo "<button type='button' class='button remove-gallery-item'>Remover</button>";
                    echo "</div>";
                }
            }

            echo "</div>";
            echo "<button type='button' id='{$name}_add_button' class='button button-primary'>Adicionar mídia</button>";
            echo "</div>";
        ?>
            <script>
                (function($) {
                    $(document).ready(function() {
                        const galleryWrapper = $('#<?php echo $name; ?>_gallery_wrapper');
                        const addButton = $('#<?php echo $name; ?>_add_button');

                        addButton.on('click', function(e) {
                            e.preventDefault();
                            const mediaUploader = wp.media({
                                title: 'Selecione mídias',
                                button: {
                                    text: 'Adicionar'
                                },
                                multiple: true
                            }).on('select', function() {
                                const attachments = mediaUploader.state().get('selection').toJSON();
                                attachments.forEach(function(attachment) {
                                    const url = attachment.url;
                                    const file_extension = url.split('.').pop().toLowerCase();
                                    const index = galleryWrapper.children('.gallery-item').length;

                                    let galleryItem = `<div class="gallery-item" data-index="${index}">`;
                                    if (['mp4', 'mov', 'avi', 'mkv', 'flv'].includes(file_extension)) {
                                        galleryItem += `<video width='131' height='131' controls>
                                                                        <source src="${url}" type="video/${file_extension}">
                                                                        Seu navegador não suporta o elemento de vídeo.
                                                                    </video>`;
                                    } else {
                                        galleryItem += `<img src="${url}" style="width: 131px; height: 131px; object-fit: cover;">`;
                                    }
                                    galleryItem += `<input type="hidden" name="<?php echo $name; ?>[]" value="${url}">`;
                                    galleryItem += `<button type="button" class="button remove-gallery-item">Remover</button>`;
                                    galleryItem += `</div>`;

                                    galleryWrapper.append(galleryItem);
                                });
                            }).open();
                        });

                        galleryWrapper.on('click', '.remove-gallery-item', function(e) {
                            e.preventDefault();
                            $(this).closest('.gallery-item').remove();
                        });
                    });
                })(jQuery);
            </script>
        <?php
        }

        private static function render_post_type_field($name, $field, $value)
        {
            if (!empty($field['options']['post_type'])) {

                $posts = new WP_Query(array(
                    'post_type' => $field['options']['post_type'],
                    'posts_per_page' => -1
                ));

                if (!is_wp_error($posts) && $posts->have_posts()) {
                    echo "<div class='group'>";
                    echo "<label for='{$name}'>{$field["label"]}</label>";
                    echo "<select id='{$name}' name='{$name}' class='selectize'>";
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
        }

        private static function render_taxonomy_field($name, $field, $value)
        {
            if (!empty($field['options']['taxonomy'])) {
                $terms = get_terms([
                    'taxonomy' => $field['options']['taxonomy'],
                    'hide_empty' => false,
                ]);

                if (!is_wp_error($terms) && !empty($terms)) {
                    echo "<div class='group'>";
                    echo "<label for='{$name}'>{$field["label"]}</label>";
                    echo "<select id='{$name}' name='{$name}'>";
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
        }

        private static function render_users_field($name, $field, $value)
        {
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
                    echo "<select id='{$name}' name='{$name}' class='selectize'>";
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
        }

        private static function render_multi_field($name, $field, $value)
        {
            echo "<div class='group multi-field'>";
            echo "<label>{$field["label"]}</label>";

            echo "<button type='button' class='button add-multi-group'>Adicionar</button>";

            echo "<div class='multi-groups'>";
            if (!empty($value) && is_array($value)) {
                foreach ($value as $index => $group) {
                    echo "<div class='multi-group' data-index='{$index}'>";
                    foreach ($field['fields'] as $sub_field) {
                        $sub_name = "{$name}[{$index}][{$sub_field['id']}]";
                        $sub_value = isset($group[$sub_field['id']]) ? $group[$sub_field['id']] : '';
                        WP_MV_RenderFields::render_field($sub_name, $sub_field, $sub_value);
                    }
                    echo "<button type='button' class='button remove-multi-group'>Excluir</button>";
                    echo "</div>";
                }
            }
            echo "</div>";
            echo "</div>";

        ?>
            <script>
                (function($) {
                    $(document).ready(function() {
                        $('.multi-field').each(function() {
                            const $multiField = $(this);
                            const $wrapper = $multiField.find('.multi-groups');
                            const $addButton = $multiField.find('.add-multi-group');

                            function getNextIndex() {
                                let nextIndex = 0;
                                $wrapper.find('.multi-group').each(function() {
                                    const index = $(this).data('index');
                                    if (index >= nextIndex) {
                                        nextIndex = index + 1;
                                    }
                                });
                                return nextIndex;
                            }

                            $addButton.on('click', function(e) {
                                e.preventDefault();

                                const index = getNextIndex();

                                let newGroup = '<div class="multi-group" data-index="' + index + '">';

                                <?php foreach ($field['fields'] as $sub_field) { ?>
                                    newGroup += `
                            <div class="group">
                                <label for="${'<?php echo $name; ?>'}[${index}][<?php echo $sub_field['id']; ?>]">
                                    <?php echo $sub_field['label']; ?>
                                </label>
                                <input type="<?php echo $sub_field['type']; ?>"
                                       name="<?php echo $name; ?>[${index}][<?php echo $sub_field['id']; ?>]"
                                       value="" />
                            </div>
                        `;
                                <?php } ?>

                                newGroup += `
                        <button type="button" class="button remove-multi-group">Remover</button>
                    </div>`;

                                $wrapper.append(newGroup);
                            });

                            $wrapper.on('click', '.remove-multi-group', function(e) {
                                e.preventDefault();
                                const $group = $(this).closest('.multi-group');
                                $group.remove();
                            });
                        });
                    });
                })(jQuery);
            </script>
<?php
        }
    }
}
