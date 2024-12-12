<?php

/* ==================================================================
Plugin Name: Metaboxs (Miqueias Vinicius)
Plugin URI: https://github.com/miqueias-vinicius/wp-mv-metaboxs
Description: Gerenciamento de metaboxs customizados e organizado em grupos
Version: 1.0.1
Requires at least: 5.0
Requires PHP: 5.2
Author: Miqueias Vinicius
Author URI: https://miqueiasvinicius.com/
License: software proprietário
Text Domain: wp_mv_metaboxs
================================================================== */

if (!defined('ABSPATH')) {
    die('Invalid request.');
}

define('WP_MV_METABOXS_PLUGIN_URI', 'https://github.com/miqueias-vinicius/wp-mv-metaboxs');
define('WP_MV_METABOXS_DIR', plugin_dir_path(__FILE__));
define('WP_MV_METABOXS_URL', plugin_dir_url(__FILE__));
define('WP_MV_METABOXS_TEXT_DOMAIN', 'wp-mv-metaboxs');

if (!class_exists('WP_MV_Metaboxs')) {
    class WP_MV_Metaboxs
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
        }

        public function assets()
        {
            wp_enqueue_style(
                "wp-mv-metaboxs",
                WP_MV_METABOXS_URL . "/style.css"
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
                WP_MV_METABOXS_URL . "/script.js"
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
                    echo "<p class='group'>";
                    echo "<input type='hidden' name='{$name}' value='0' />";
                    echo "<label for='{$name}'>{$field["label"]}</label>";
                    echo "<input {$mask} type='checkbox' id='{$name}' name='{$name}' value='1' {$checked} />";
                    echo "</p>";
                    break;
                case "textarea":
                    echo "<p class='group'>";
                    echo "<label for='{$name}'>{$field["label"]}</label>";
                    echo "<textarea {$mask} id='{$name}' name='{$name}'>" . esc_textarea($value) . "</textarea>";
                    echo "</p>";
                    break;
                case "select":
                    echo "<p class='group'>";
                    echo "<label for='{$name}'>{$field["label"]}</label>";
                    echo "<select {$mask} id='{$name}' name='{$name}'>";
                    echo "<option value='no_set'>Selecione uma opção</option>";

                    foreach ($field['options'] as $option_value => $option_label) {
                        $selected = selected($value, $option_value, false);
                        echo "<option value='{$option_value}' {$selected}>{$option_label}</option>";
                    }

                    echo "</select>";
                    echo "</p>";

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
                case "post_type":
                    if (!empty($field['options']['post_type'])) {

                        $posts = new WP_Query(array(
                            'post_type' => $field['options']['post_type'],
                            'posts_per_page' => -1
                        ));

                        if (!is_wp_error($posts) && $posts->have_posts()) {
                            echo "<p class='group'>";
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
                            echo "</p>";

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
                            echo "<p class='group'>";
                            echo "<label>{$field["label"]}</label>";
                            echo "<em>Não foram encontrados posts para '{$field['options']['post_type']}'</em>";
                            echo "</p>";
                        }
                    } else {
                        echo "<p class='group'>";
                        echo "<label>{$field["label"]}</label>";
                        echo "<em>O tipo de post não foi especificada no campo.</em>";
                        echo "</p>";
                    }
                    break;
                case "taxonomy":
                    if (!empty($field['options']['taxonomy'])) {
                        $terms = get_terms([
                            'taxonomy' => $field['options']['taxonomy'],
                            'hide_empty' => false,
                        ]);

                        if (!is_wp_error($terms) && !empty($terms)) {
                            echo "<p class='group'>";
                            echo "<label for='{$name}'>{$field["label"]}</label>";
                            echo "<select {$mask} id='{$name}' name='{$name}'>";
                            echo "<option value='no_set'>Selecione uma opção</option>";

                            foreach ($terms as $term) {
                                $selected = selected($value, $term->term_id, false);
                                echo "<option value='{$term->term_id}' {$selected}>{$term->name}</option>";
                            }

                            echo "</select>";
                            echo "</p>";

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
                            echo "<p class='group'>";
                            echo "<label>{$field["label"]}</label>";
                            echo "<em>Não foram encontrados termos para a taxonomia '{$field['options']['taxonomy']}'</em>";
                            echo "</p>";
                        }
                    } else {
                        echo "<p class='group'>";
                        echo "<label>{$field["label"]}</label>";
                        echo "<em>A taxonomia não foi especificada no campo.</em>";
                        echo "</p>";
                    }
                    break;
                default:
                    echo "<p class='group'>";
                    echo "<label for='{$name}'>{$field["label"]}</label>";
                    echo "<input {$mask} id='{$name}' type='{$field["type"]}' name='{$name}' value='" . esc_attr($value) . "' />";
                    echo "</p>";
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
                        $value = call_user_func($sanitize_callback, $_POST[$name]);
                        update_post_meta($post_id, $name, $value);
                    } else {
                        delete_post_meta($post_id, $name);
                    }
                }
            }
        }
    }
}
