<?php
if (!defined('ABSPATH')) {
    exit();
}
// Exit if accessed directly
?>

<div id="orkestapay-container">              
        <p class="orkesta-cards-copy"><?php esc_html_e('Accepted cards', 'orkestapay'); ?></p>

        <div class="orkesta-credit-cards">
            <?php foreach ($this->brands as $item): ?>   
                <img alt="<?php echo esc_attr($item->brand); ?>" src="<?php echo esc_url($item->logo); ?>" />
            <?php endforeach; ?>           
        </div>

        <p><?php echo esc_html($this->description); ?></p>            
</div>