<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */
if(!empty($attributes['selectedCategory'])){
    $category = $attributes['selectedCategory'];
}
if(!empty($attributes['selectedPosts'])){
    $postid = $attributes['selectedPosts'];
}
if(!empty($attributes['selectedPersonIds'])){
    $identifier = $attributes['selectedPersonIds'];
}
if(!empty($attributes['selectedFields'])){
    $showFields = $attributes['selectedFields'];
}
if(!empty($attributes['selectedFormat'])){
    $format = $attributes['selectedFormat'];
}
if(!empty($attributes['groupId'])){
    $groupid = $attributes['groupId'];
}
if(!empty($attributes['functionField'])){
    $function = $attributes['functionField'];
}
if(!empty($attributes['organizationNr'])){
    $orgnr = $attributes['organizationNr'];
}
if(!empty($attributes['url'])){
    $url = $attributes['url'];
}

// Build shortcode parameters
$shortcode_params = array();

if (!empty($category)) {
    $shortcode_params[] = 'category=' . esc_attr($category);
}

if (!empty($identifier)) {
    if (is_array($identifier)) {
        $identifier = implode(',', $identifier);
    }
    $shortcode_params[] = 'identifier=' . esc_attr($identifier);
}

if (!empty($showFields)) {
    if (is_array($showFields)) {
        $showFields = implode(',', $showFields);
    }
    $shortcode_params[] = 'show=' . esc_attr($showFields);
}
if (!empty($format)) {
    if (is_array($format)) {
        $format = implode(',', $format);
    }
    $shortcode_params[] = 'format=' . esc_attr($format);
}
if (!empty($groupid)) {
    if (is_array($groupid)) {
        $groupid = implode(',', $groupid);
    }
    $shortcode_params[] = 'groupid=' . esc_attr($groupid);
}
if (!empty($function)) {
    if (is_array($function)) {
        $function = implode(',', $function);
    }
    $shortcode_params[] = 'function=' . esc_attr($function);
}
if (!empty($orgnr)) {
    if (is_array($orgnr)) {
        $orgnr = implode(',', $orgnr);
    }
    $shortcode_params[] = 'orgnr=' . esc_attr($orgnr);
}
if (!empty($url)) {
    if (is_array($url)) {
        $url = implode(',', $url);
    }
    $shortcode_params[] = 'url=' . esc_attr($url);
}

// Construct the shortcode
$shortcode = '[faudir';
if (!empty($shortcode_params)) {
    $shortcode .= ' ' . implode(' ', $shortcode_params);
}
$shortcode .= ']';
?>

<p <?php echo get_block_wrapper_attributes(); ?>>
<?php echo implode('<br> ', $shortcode_params);?>
<?php echo do_shortcode($shortcode); ?>
</p>