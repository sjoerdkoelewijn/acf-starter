# Product photos

Put your photos in this folder. JPG, PNG, WebP and AVIF all work.

The seeder matches a photo to a product by file name, through the `images`
column in `demo/products.csv`. Several photos for one product go in the same
cell, split by a pipe:

    AS-001,Merino crew jumper,clothing,129.00,,1,12,...,jumper-front.jpg|jumper-back.jpg

**You do not have to use this folder at all.** The seeder also reads the
WordPress media library, so uploading under **Media → Add new** works just as
well and needs no SFTP. A file here wins over a library photo with the same
name.

**You do not have to fill the CSV column first either.** When the column is
empty, or when the file it names is nowhere, the seeder takes the next photo
it has and moves on. So drop your photos in, run the script, and edit the
names later.

With no photos at all the demo still builds. Each card then shows the grey
placeholder box from the theme CSS.

Keep the files out of git unless they are small. The `.gitignore` in this
folder already does that.
