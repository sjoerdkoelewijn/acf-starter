<?php /* single image or image slider */ ?>

<div class="image_wrap">

    <?php 
    $images = get_field('image'); 
    
    if( $images ): 
        if( count($images) === 1 ) { 

            foreach( $images as $image ): ?>
                        
                <img loading="lazy" class="image" src="<?php echo esc_url($image['sizes']['large']); ?>" alt="<?php echo $image['alt']; ?>" />
        
                <?php if($image['caption']) { ?>
        
                    <p class="caption">
                        <?php echo esc_html($image['caption']); ?>
                    </p>
        
                <?php } 
            
            endforeach;    

        } else { ?> 

            <div class="slider_wrap">

                <?php foreach( $images as $image ): ?>

                    <div class="">
                                
                        <img loading="lazy" class="image" src="<?php echo esc_url($image['sizes']['large']); ?>" alt="<?php echo $image['alt']; ?>" />
                
                        <?php if($image['caption']) { ?>
                
                            <p class="caption">
                                <?php echo esc_html($image['caption']); ?>
                            </p>
                
                        <?php } ?>

                    </div>
                
                <?php endforeach; ?>

            </div>

        <?php } ?>        
        
    <?php endif; ?>

</div> 