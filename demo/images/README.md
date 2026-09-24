# Product photos

Put your photos in this folder. JPG, PNG, WebP and AVIF all work.

The seeder matches a photo to a product by file name, through the `images`
column in `demo/products.csv`. Several photos for one product go in the same
cell, split by a pipe:

    AS-001,Merino crew jumper,clothing,129.00,,1,12,...,jumper-front.jpg|jumper-back.jpg

**You do not have to fill that column first.** When the column is empty, or
when the file it names is not here, the seeder takes the next photo in this
folder and moves on. So you can drop your photos in, run the script, and edit
the names later.

With no photos at all the demo still builds. Each card then shows the grey
placeholder box from the theme CSS.

Keep the files out of git unless they are small. The `.gitignore` in this
folder already does that.
