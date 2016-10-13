<?php

return array(

  /*
   * Library used to manipulate image.
   *
   * Options: gd (default), imagick, gmagick
   */
  'library' => 'gd',

  /*
   * Quality for JPEG type.
   *
   * Scale: 1-100;
   */
  'quality' => 90,

  /*
   * Upload directory.
   *
   * Default: public/uploads/images
   */
  'path' => public_path().'/uploads/images',

  /*
    * Use original name. If set to false, will use hashed name.
    *
    * Options:
    *     - original (default): use original filename in "slugged" name
    *     - hash: use filename hash as new file name
    *     - random: use random generated new file name
    *     - timestamp: use uploaded timestamp as filename
    *     - custom: user must provide new name, if not will use original filename
    */
  'newfilename' => 'timestamp',

  /*
   * Sizes, used to crop and create multiple size.
   *
   * array(width, height, square, quality), if square set to TRUE, image will be in square
   */
   'dimensions' => array(
//'square100' => array(100, 100, true),
     
   ),

   /*
    * Dimension identifier. If TRUE will use dimension name as suffix, if FALSE use directory.
    *
    * Example:
    *     - TRUE (default): newname_square50.png, newname_size100.jpg
    *     - FALSE: square50/newname.png, size100/newname.jpg
    */
   'suffix' => true,
);