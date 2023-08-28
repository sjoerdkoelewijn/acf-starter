<?php
/* Block Name: Hero Block */

// create id attribute for specific styling
$id = 'block-' . $block['id'];

?>

<section class="hero-block" id="<?php echo $id; ?>" class="right">

    <?php include('parts/text.php'); ?>  

    <?php include('parts/image.php'); ?>              
            
</section>