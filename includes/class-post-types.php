<?php

if (!class_exists('WP_MV_PostType')) {
    class WP_MV_PostType
    {
        private $post_type;
        private $args;

        public function __construct($post_type, $args = array())
        {
            $this->post_type = $post_type;
            $this->args = $args;

            add_action('init', [$this, 'register']);
        }

        public function register()
        {
            $labels = $this->build_labels();
            $args = $this->build_args($labels);

            register_post_type($this->post_type, $args);
        }

        private function build_labels()
        {
            $name = $this->args['name'] ?? ucfirst($this->post_type);
            $singular_name = $this->args['singular_name'] ?? $name;

            $is_feminine = strtolower(substr($singular_name, -1)) === 'a';

            return [
                'name'               => esc_html__($name, WP_MV_METABOXS_TEXT_DOMAIN),
                'singular_name'      => esc_html__($singular_name, WP_MV_METABOXS_TEXT_DOMAIN),
                'menu_name'          => esc_html__($name, WP_MV_METABOXS_TEXT_DOMAIN),
                'name_admin_bar'     => esc_html__($singular_name, WP_MV_METABOXS_TEXT_DOMAIN),
                'add_new'            => esc_html__($is_feminine ? 'Adicionar nova' : 'Adicionar novo', WP_MV_METABOXS_TEXT_DOMAIN),
                'add_new_item'       => sprintf(esc_html__($is_feminine ? 'Adicionar nova %s' : 'Adicionar novo %s', WP_MV_METABOXS_TEXT_DOMAIN), strtolower($singular_name)),
                'new_item'           => sprintf(esc_html__($is_feminine ? 'Nova %s' : 'Novo %s', WP_MV_METABOXS_TEXT_DOMAIN), strtolower($singular_name)),
                'edit_item'          => sprintf(esc_html__('Editar %s', WP_MV_METABOXS_TEXT_DOMAIN), strtolower($singular_name)),
                'view_item'          => sprintf(esc_html__('Ver %s', WP_MV_METABOXS_TEXT_DOMAIN), strtolower($singular_name)),
                'all_items'          => sprintf(esc_html__($is_feminine ? 'Todos as %s' : 'Todos os %s', WP_MV_METABOXS_TEXT_DOMAIN), strtolower($name)),
                'search_items'       => sprintf(esc_html__('Procurar %s', WP_MV_METABOXS_TEXT_DOMAIN), strtolower($name)),
                'parent_item_colon'  => sprintf(esc_html__('%s pai:', WP_MV_METABOXS_TEXT_DOMAIN), $name),
                'not_found'          => sprintf(esc_html__($is_feminine ? 'Nenhuma %s encontrada.' : 'Nenhum %s encontrado.', WP_MV_METABOXS_TEXT_DOMAIN), strtolower($singular_name)),
                'not_found_in_trash' => sprintf(esc_html__($is_feminine ? 'Nenhuma %s encontrada na lixeira.' : 'Nenhum %s encontrado na lixeira.', WP_MV_METABOXS_TEXT_DOMAIN), strtolower($singular_name)),
            ];
        }

        private function build_args($labels)
        {
            return [
                'labels'             => $labels,
                'public'             => $this->args['public'] ?? true,
                'has_archive'        => $this->args['has_archive'] ?? true,
                'rewrite'            => ['slug' => $this->args['slug'] ?? $this->post_type],
                'menu_icon'          => $this->args['menu_icon'] ?? 'dashicons-edit-page',
                'supports'           => $this->args['supports'] ?? ['title', 'editor', 'thumbnail', 'excerpt'],
                'show_in_rest'       => $this->args['show_in_rest'] ?? true,
                'hierarchical'       => $this->args['hierarchical'] ?? false,
                'menu_position'      => $this->args['menu_position'] ?? 5,
            ];
        }
    }
}
