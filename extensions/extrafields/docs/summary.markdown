Extra Image Field
=================

This is an extension that adds two extra fields to entries and pages. These
fields allow you to add an image, outside of the regular 'introduction' or
'body' fields. The main advantage of this is, that you can use this image in
different ways on different pages. For instance, you can use one image as a
small thumbnail on the frontpage of your site, and as a slightly larger
thumbnail on the entrypage, that clicks through to the full-size image.

<img src="extensions/extrafields/docs/extraimagefield.png" alt="screenshot" style='border: 1px solid #CCC;'/>

Using this image in your template is as simple as:

    <img class="extraimg" src="/images/[[$entry.extrafields.image]]" />
    <small>[[$entry.extrafields.image_description]]</small>

Normally, you'd use this functionality to add a thumbnail to a page. In the
following example, we also check for an image. When there's no image, we output
nothing to prevent broken images.

    [[ if $entry.extrafields.image!="" ]]
    <div class="extraimage">
    <img src="/pivotx/includes/timthumb.php?src=[[$entry.extrafields.image]]&w=100&h=100"
                alt="[[$entry.extrafields.description]]" />
    </div> 
    [[ /if ]]            

Then, on the entrypage, you might want to show it slightly larger, with a
click-through to the entire image. Like this:

    [[ if $entry.extrafields.image!="" ]]
    <div class="extraimage">
    <a href="/images/[[$entry.extrafields.image]]" class="thickbox"
                title="[[$entry.extrafields.description]]">
    <img src="/pivotx/includes/timthumb.php?src=[[$entry.extrafields.image]]&w=140&h=140"
                alt="[[$entry.extrafields.description]]" />
    </a>
    <p class="ankeiler">[[$entry.extrafields.description]]</p>           
    </div> 
    [[ /if ]]





