<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php //body_class('antialiased'); ?>>

    <?php get_template_part('parts/section-header'); ?>
    <?php get_template_part('parts/breadcrumbs'); ?>

    <main class="prose-sm max-w-none pt-10 pb-10">
