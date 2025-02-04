<?php
/*
Plugin Name: Creation Post Type
Description: Create and manage custom post types, taxonomies, and meta boxes.
Version: 1.0
Author: Mahmoud Hosny
Text Domain: custom-content-manager
*/

// Prevent direct access
if (!defined('ABSPATH')) exit;

class CustomContentManager
{
    private $cpt_option = 'ccm_custom_post_types';
    private $tax_option = 'ccm_custom_taxonomies';
    private $meta_option = 'ccm_custom_meta_boxes';
    private $current_meta_box_fields = [];

    public function __construct()
    {
        register_activation_hook(__FILE__, [$this, 'activate']);
        // register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall']);

        add_action('init', [$this, 'register_custom_content']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'admin_styles']);
        add_action('admin_post_ccm_save_cpt', [$this, 'save_cpt']);
        add_action('admin_post_ccm_save_taxonomy', [$this, 'save_taxonomy']);


        add_action('admin_post_ccm_save_meta_box', [$this, 'save_meta_box']);
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_box_data']);

        add_action('wp_ajax_ccm_add_field', [$this, 'ajax_add_field']);
    }

    public function activate()
    {
        // Initialize options if they don't exist
        add_option($this->cpt_option, []);
        add_option($this->tax_option, []);
        add_option($this->meta_option, []);
    }

    public static function uninstall()
    {
        delete_option('ccm_custom_post_types');
        delete_option('ccm_custom_taxonomies');
        delete_option('ccm_custom_meta_boxes');
    }

    public function admin_menu()
    {
        add_menu_page(
            'Content Manager',
            'Content Manager',
            'manage_options',
            'content-manager',
            [$this, 'main_page'],
            'dashicons-admin-generic',
            60
        );

        add_submenu_page(
            'content-manager',
            'Custom Post Types',
            'Post Types',
            'manage_options',
            'ccm-post-types',
            [$this, 'cpt_page']
        );

        add_submenu_page(
            'content-manager',
            'Custom Taxonomies',
            'Taxonomies',
            'manage_options',
            'ccm-taxonomies',
            [$this, 'taxonomy_page']
        );

        // Add to admin_menu() method
        add_submenu_page(
            'content-manager',
            'Meta Boxes',
            'Meta Boxes',
            'manage_options',
            'ccm-meta-boxes',
            [$this, 'meta_box_page']
        );
    }

    public function admin_styles()
    {
        wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
        wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', [], null, true);
    }

    public function main_page()
    {
        $cpts = get_option($this->cpt_option, []);
        $taxonomies = get_option($this->tax_option, []);
        $meta_boxes = get_option($this->meta_option, []); ?>

        <div class="container mt-4">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="p-5 bg-primary text-white rounded-3">
                        <h1 class="display-6">Content Manager Dashboard</h1>
                        <p class="lead">Manage all your custom content types and meta boxes</p>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Statistics Cards -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <span class="badge bg-primary"><?= count($cpts) ?></span>
                                Post Types
                            </h5>
                            <a href="<?= admin_url('admin.php?page=ccm-post-types') ?>" class="btn btn-outline-primary btn-sm">
                                Manage Post Types
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <span class="badge bg-success"><?= count($taxonomies) ?></span>
                                Taxonomies
                            </h5>
                            <a href="<?= admin_url('admin.php?page=ccm-taxonomies') ?>" class="btn btn-outline-success btn-sm">
                                Manage Taxonomies
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <span class="badge bg-info"><?= count($meta_boxes) ?></span>
                                Meta Boxes
                            </h5>
                            <a href="<?= admin_url('admin.php?page=ccm-meta-boxes') ?>" class="btn btn-outline-info btn-sm">
                                Manage Meta Boxes
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Items Section -->
            <div class="row mt-5">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Recent Post Types</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($cpts)) : ?>
                                <div class="list-group">
                                    <?php foreach (array_slice($cpts, -3) as $cpt) : ?>
                                        <a href="<?= admin_url('admin.php?page=ccm-post-types') ?>"
                                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                            <?= esc_html($cpt['name']) ?>
                                            <small class="text-muted"><?= esc_html($cpt['slug']) ?></small>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <div class="alert alert-warning">No post types created yet!</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Recent Taxonomies</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($taxonomies)) : ?>
                                <div class="list-group">
                                    <?php foreach (array_slice($taxonomies, -3) as $tax) : ?>
                                        <a href="<?= admin_url('admin.php?page=ccm-taxonomies') ?>"
                                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                            <?= esc_html($tax['name']) ?>
                                            <small class="text-muted"><?= esc_html($tax['slug']) ?></small>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <div class="alert alert-warning">No taxonomies created yet!</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#cptModal">
                                    Create New Post Type
                                </button>
                                <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#taxModal">
                                    Create New Taxonomy
                                </button>
                                <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#metaBoxModal">
                                    Create New Meta Box
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }


    public function cpt_page()
    {
        $cpts = get_option($this->cpt_option, []); ?>
        <div class="container mt-4">
            <h2>Custom Post Types</h2>
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#cptModal">
                Create New Post Type
            </button>

            <div class="row">
                <?php foreach ($cpts as $cpt) : ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?= esc_html($cpt['name']) ?></h5>
                                <p class="card-text"><?= esc_html($cpt['description']) ?></p>
                                <small class="text-muted">Slug: <?= esc_html($cpt['slug']) ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- CPT Creation Modal -->
            <div class="modal fade" id="cptModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="<?= admin_url('admin-post.php') ?>" method="POST">
                            <input type="hidden" name="action" value="ccm_save_cpt">
                            <?php wp_nonce_field('ccm_cpt_nonce', 'ccm_cpt_nonce'); ?>

                            <div class="modal-header">
                                <h5 class="modal-title">Create New Post Type</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Post Type Name</label>
                                    <input type="text" name="cpt_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Slug</label>
                                    <input type="text" name="cpt_slug" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="cpt_description" class="form-control"></textarea>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

    public function save_cpt()
    {
        check_admin_referer('ccm_cpt_nonce', 'ccm_cpt_nonce');

        $cpts = get_option($this->cpt_option, []);

        $new_cpt = [
            'name' => sanitize_text_field($_POST['cpt_name']),
            'slug' => sanitize_title($_POST['cpt_slug']),
            'description' => sanitize_textarea_field($_POST['cpt_description'])
        ];

        $cpts[] = $new_cpt;
        update_option($this->cpt_option, $cpts);

        wp_redirect(admin_url('admin.php?page=ccm-post-types'));
        exit;
    }


    public function taxonomy_page()
    {
        $taxonomies = get_option($this->tax_option, []);
        $cpts = get_option($this->cpt_option, []); ?>

        <div class="container mt-4">
            <h2>Custom Taxonomies</h2>
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#taxModal">
                Create New Taxonomy
            </button>

            <div class="row">
                <?php foreach ($taxonomies as $tax) : ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?= esc_html($tax['name']) ?></h5>
                                <p class="card-text"><?= esc_html($tax['description']) ?></p>
                                <small class="text-muted">
                                    Slug: <?= esc_html($tax['slug']) ?><br>
                                    Post Types: <?= implode(', ', $tax['post_types']) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Taxonomy Modal -->
            <div class="modal fade" id="taxModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="<?= admin_url('admin-post.php') ?>" method="POST">
                            <input type="hidden" name="action" value="ccm_save_taxonomy">
                            <?php wp_nonce_field('ccm_tax_nonce', 'ccm_tax_nonce'); ?>

                            <div class="modal-header">
                                <h5 class="modal-title">Create New Taxonomy</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Taxonomy Name</label>
                                    <input type="text" name="tax_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Slug</label>
                                    <input type="text" name="tax_slug" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="tax_description" class="form-control"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Associated Post Types</label>
                                    <select name="tax_post_types[]" class="form-select" multiple>
                                        <?php foreach ($cpts as $cpt) : ?>
                                            <option value="<?= esc_attr($cpt['slug']) ?>">
                                                <?= esc_html($cpt['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

    public function save_taxonomy()
    {
        check_admin_referer('ccm_tax_nonce', 'ccm_tax_nonce');

        $taxonomies = get_option($this->tax_option, []);

        $new_tax = [
            'name' => sanitize_text_field($_POST['tax_name']),
            'slug' => sanitize_title($_POST['tax_slug']),
            'description' => sanitize_textarea_field($_POST['tax_description']),
            'post_types' => array_map('sanitize_text_field', $_POST['tax_post_types'])
        ];

        $taxonomies[] = $new_tax;
        update_option($this->tax_option, $taxonomies);

        wp_redirect(admin_url('admin.php?page=ccm-taxonomies'));
        exit;
    }

    public function register_custom_content()
    {
        $cpts = get_option($this->cpt_option, []);
        $taxonomies = get_option($this->tax_option, []);

        // Register CPTs
        foreach ($cpts as $cpt) {
            register_post_type($cpt['slug'], [
                'label' => $cpt['name'],
                'description' => $cpt['description'],
                'public' => true,
                'show_ui' => true,
                'supports' => ['title', 'editor', 'thumbnail']
            ]);
        }

        // Register Taxonomies
        foreach ($taxonomies as $tax) {
            register_taxonomy($tax['slug'], $tax['post_types'], [
                'label' => $tax['name'],
                'description' => $tax['description'],
                'public' => true,
                'show_admin_column' => true
            ]);
        }
    }
    public function meta_box_page()
    {
        $meta_boxes = get_option($this->meta_option, []);
        $cpts = get_option($this->cpt_option, []); ?>

        <div class="container mt-4">
            <h2>Custom Meta Boxes</h2>
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#metaBoxModal">
                Create New Meta Box
            </button>

            <div class="row">
                <?php foreach ($meta_boxes as $meta) : ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?= esc_html($meta['title']) ?></h5>
                                <p class="card-text">Post Type: <?= esc_html($meta['post_type']) ?></p>
                                <div class="fields-list">
                                    <?php foreach ($meta['fields'] as $field) : ?>
                                        <span class="badge bg-secondary"><?= $field['type'] ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Meta Box Modal -->
            <div class="modal fade" id="metaBoxModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form action="<?= admin_url('admin-post.php') ?>" method="POST" id="metaBoxForm">
                            <input type="hidden" name="action" value="ccm_save_meta_box">
                            <?php wp_nonce_field('ccm_meta_nonce', 'ccm_meta_nonce'); ?>

                            <div class="modal-header">
                                <h5 class="modal-title">Create Meta Box</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Meta Box Title</label>
                                        <input type="text" name="meta_title" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Post Type</label>
                                        <select name="meta_post_type" class="form-select" required>
                                            <?php foreach ($cpts as $cpt) : ?>
                                                <option value="<?= esc_attr($cpt['slug']) ?>">
                                                    <?= esc_html($cpt['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-4" id="metaFieldsContainer">
                                    <h5>Fields</h5>
                                    <button type="button" class="btn btn-sm btn-secondary mb-2" id="addField">
                                        Add Field +
                                    </button>

                                    <div class="field-template d-none">
                                        <div class="card mb-3 field-item">
                                            <div class="card-body">
                                                <div class="row g-2">
                                                    <div class="col-md-4">
                                                        <input type="text" name="fields[][label]"
                                                            class="form-control" placeholder="Label" required>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <input type="text" name="fields[][id]"
                                                            class="form-control" placeholder="ID" required>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <select name="fields[][type]" class="form-select">
                                                            <option value="text">Text</option>
                                                            <option value="textarea">Textarea</option>
                                                            <option value="select">Select</option>
                                                            <option value="checkbox">Checkbox</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button type="button"
                                                            class="btn btn-danger btn-sm remove-field">×</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div> <!-- .field-template -->
                                </div> <!-- #metaFieldsContainer -->
                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">Save Meta Box</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                $('#addField').click(function() {
                    const template = $('.field-template .field-item').clone();
                    $('#metaFieldsContainer').append(template);
                });

                $(document).on('click', '.remove-field', function() {
                    $(this).closest('.field-item').remove();
                });
            });
        </script>
    <?php
    }

    public function save_meta_box()
    {
        check_admin_referer('ccm_meta_nonce', 'ccm_meta_nonce');

        $meta_boxes = get_option($this->meta_option, []);

        $new_meta = [
            'title' => sanitize_text_field($_POST['meta_title']),
            'post_type' => sanitize_text_field($_POST['meta_post_type']),
            'fields' => []
        ];

        foreach ($_POST['fields'] as $field) {
            $new_meta['fields'][] = [
                'label' => sanitize_text_field($field['label']),
                'id' => sanitize_key($field['id']),
                'type' => sanitize_text_field($field['type'])
            ];
        }

        $meta_boxes[] = $new_meta;
        update_option($this->meta_option, $meta_boxes);

        wp_redirect(admin_url('admin.php?page=ccm-meta-boxes'));
        exit;
    }

    public function register_meta_boxes()
    {
        $meta_boxes = get_option($this->meta_option, []);

        foreach ($meta_boxes as $meta) {
            add_meta_box(
                sanitize_key($meta['title']),
                $meta['title'],
                function ($post) use ($meta) {
                    $this->render_meta_box($post, $meta);
                },
                $meta['post_type']
            );
        }
    }


    public function render_meta_box($post, $meta)
    {
        wp_nonce_field('ccm_meta_box_data', 'ccm_meta_box_nonce');

        echo '<div class="ccm-meta-box">';
        foreach ($meta['fields'] as $field) {
            $value = get_post_meta($post->ID, $field['id'], true);
            echo '<div class="mb-3">';
            echo '<label class="form-label">' . esc_html($field['label']) . '</label>';

            switch ($field['type']) {
                case 'text':
                    echo '<input type="text" name="' . esc_attr($field['id']) . '" 
                           class="form-control" value="' . esc_attr($value) . '">';
                    break;
                case 'textarea':
                    echo '<textarea name="' . esc_attr($field['id']) . '" 
                              class="form-control">' . esc_textarea($value) . '</textarea>';
                    break;
                case 'select':
                    // Add options handling
                    break;
            }
            echo '</div>';
        }
        echo '</div>';
    }

    public function save_meta_box_data($post_id)
    {
        // Check if we're doing an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Verify nonce
        if (
            !isset($_POST['ccm_meta_box_nonce']) ||
            !wp_verify_nonce($_POST['ccm_meta_box_nonce'], 'ccm_meta_box_data')
        ) {
            return;
        }

        $meta_boxes = get_option($this->meta_option, []);

        foreach ($meta_boxes as $meta) {
            foreach ($meta['fields'] as $field) {
                if (isset($_POST[$field['id']])) {
                    update_post_meta(
                        $post_id,
                        $field['id'],
                        sanitize_text_field($_POST[$field['id']])
                    );
                } else {
                    delete_post_meta($post_id, $field['id']);
                }
            }
        }
    }

    public function ajax_add_field()
    {
        check_ajax_referer('ccm_meta_nonce', 'nonce');

        ob_start(); ?>
        <div class="card mb-3 field-item">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="fields[][label]"
                            class="form-control" placeholder="Label" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="fields[][id]"
                            class="form-control" placeholder="ID" required>
                    </div>
                    <div class="col-md-3">
                        <select name="fields[][type]" class="form-select">
                            <option value="text">Text</option>
                            <option value="textarea">Textarea</option>
                            <option value="select">Select</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm remove-field">×</button>
                    </div>
                </div>
            </div>
        </div>
<?php
        wp_send_json_success(ob_get_clean());
    }
}

new CustomContentManager();
