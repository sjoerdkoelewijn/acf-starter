<?php /* Text block with buttons */ ?>

<div class="text_wrap">

    <strong class="subheader">
        <?php the_field('subheader'); ?>
    </strong>

    <h2 class="header">
        <?php the_field('header'); ?>
    </h2>    

    <div class="description">
        <?php the_field('content'); ?>
    </div>

    <?php  if( have_rows('buttons') ): ?> 

        <div class="button_repeater">

            <?php while( have_rows('buttons') ): the_row(); 

                $anchor = get_sub_field('anchor');
                $url = get_sub_field('url');
                
                $ButtonType = get_sub_field('button_style');

                    switch ($ButtonType) {
                        
                        case 'action': ?>
                            
                            <a href="<?php echo $url; ?>" class="btn action_btn black" >
                                <?php echo $anchor; ?>
                            </a>

                            <?php break;
                        case 'secondary': ?>
                            
                            <a href="<?php echo $url; ?>" class="btn secondary_btn black" >
                                <?php echo $anchor; ?>
                            </a>

                            <?php break;

                        case 'textlink': ?> 

                            <a href="<?php echo $url; ?>" class="read_more_link" >
                                <?php echo $anchor; ?>
                            </a>

                            <?php break;    

                        default: ?>
                            
                            <a href="<?php echo $url; ?>" class="read_more_link" >

                                <?php echo $anchor; ?>
                            </a>

                    <?php }  

            endwhile; ?>   

        </div>

    <?php endif; ?>       
           
</div>