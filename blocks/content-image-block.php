<?php
/* Block Name: Content Image Block */

$id = $block['id'];
$imageposition = get_field('image_position'); 
?>

<section class="content-image-block <?php echo $imageposition; ?>" id="<?php echo $id; ?>">
   
    <?php include('parts/image.php'); ?> 

    <?php include('parts/text.php'); ?>  

</section>