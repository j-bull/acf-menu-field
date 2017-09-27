<?php

namespace Cite\ACFNavMenuField;

/**
 * Class ACFNavMenuField
 */
class ACFNavMenuField extends \acf_field
{
    /**
     * ACFNavMenuField constructor.
     */
    public function __construct()
    {
        $this->name = 'nav_menu';
        $this->label = __('Nav Menu');
        $this->category = 'relational';
        $this->defaults = [
            'save_format' => 'id',
            'allow_null' => 0,
            'container' => 'div',
        ];

        parent::__construct();
    }

    /**
     *  Use the ACF API to render the field options.
     * @param $field
     */
    public function render_field_settings($field)
    {
        acf_render_field_setting($field, [
            'label' => __('Return Value'),
            'type' => 'radio',
            'name' => 'save_format',
            'layout' => 'horizontal',
            'choices' => [
                'object' => __('Nav Menu Object'),
                'menu' => __('Nav Menu HTML'),
                'id' => __('Nav Menu ID'),
            ],
        ]);

        acf_render_field_setting($field, [
            'label' => __('Menu Container'),
            'type' => 'select',
            'name' => 'container',
            'choices' => $this->allowed_nav_container_tags(),
        ]);

        acf_render_field_setting($field, [
            'label' => __('Allow Null?'),
            'type' => 'radio',
            'name' => 'allow_null',
            'layout' => 'horizontal',
            'choices' => [
                1 => __('Yes'),
                0 => __('No'),
            ],
        ]);
    }

    /**
     * @return array
     */
    private function allowed_nav_container_tags()
    {
        $tags = apply_filters('wp_nav_menu_container_allowedtags', ['div', 'nav']);
        $formatted_tags = [
            '0' => 'None',
        ];

        foreach ($tags as $tag)
            $formatted_tags[$tag] = ucfirst($tag);

        return $formatted_tags;
    }

    /**
     * Render a select populated with the Wordpress registered menus.
     * @param $field
     */
    public function render_field($field)
    {
        $nav_menus = $this->nav_menus($field['allow_null']);

        if (!$nav_menus)
            return;
        ?>

        <select id="<?php esc_attr($field['id']); ?>" class="<?php echo esc_attr($field['class']); ?>"
                name="<?php echo esc_attr($field['name']); ?>">
            <?php foreach ($nav_menus as $nav_menu_id => $nav_menu_name) : ?>
                <option value="<?php echo esc_attr($nav_menu_id); ?>" <?php selected($field['value'], $nav_menu_id); ?>>
                    <?php echo esc_html($nav_menu_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Fetch the Wordpress registered menus and populate the array to be shown in the select.
     * @param bool $allow_null
     * @return array | bool
     */
    private function nav_menus($allow_null = false)
    {
        $nav_menus = [];

        if ($allow_null)
            $nav_menus[] = ' - Select - ';

        foreach (get_terms('nav_menu', ['hide_empty' => false]) as $nav)
            $nav_menus[$nav->term_id] = $nav->name;

        if (!empty($nav_menus))
            return $nav_menus;

        return false;
    }

    /**
     * Return a nav object, html, or ID.
     * @param $value
     * @param $field
     * @return bool|stdClass|string
     */
    public function format_value($value, $field)
    {
        if (empty($value))
            return false;

        if ('object' == $field['save_format']) {
            $wp_menu_object = wp_get_nav_menu_object($value);

            if (empty($wp_menu_object))
                return false;

            $menu_object = new stdClass;

            $menu_object->ID = $wp_menu_object->term_id;
            $menu_object->name = $wp_menu_object->name;
            $menu_object->slug = $wp_menu_object->slug;
            $menu_object->count = $wp_menu_object->count;

            return $menu_object;

        } elseif ('menu' == $field['save_format']) {
            ob_start();

            wp_nav_menu([
                'menu' => $value,
                'container' => $field['container']
            ]);

            return ob_get_clean();
        }

        return $value;
    }
}
