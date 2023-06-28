
Bonusfields Extension
=====================

An extension to add extra fields to pages or entries. Extrafields contain
extra page or entry information. This extension allows you to edit fields
in various ways:

Simple input types:

  * input text
  * textarea
  * select
  * radio
  * file-uploaders

Advanced types include:

  * wysiwyg
  * image
  * date/datetime
  * page/uri selection
  * choose parent page
  * choose category/chapter
  * separator line
  * view-only types

Additional features:

  * add field only when certain categories/chapter are/is selected
  * upcoming multilingual PivotX-support
  * fill select's or radio's with an SQL query
  * fill select's or radio's with your own callback
  * hierarchal page implementation when using 'choose parent page'
    * automatically adds 'hierarchal view', entries&pages > hierarchy
    * adds Smarty tags for hierarchal views, traversal and breadcrumbs

Installation
------------

After enabling the extension you should go to the Configuration tab 'Bonus
Fields', it will configure itself from there.
If you used 'Ultimate Fields' it will automatically import it's
configuration. Don't forget to disable ultimate fields after that.

Usage
-----

In the configuration tab you can add, reorder, delete and edit the
extrafield input types. Removing an input type does not remove the
extrafield in PivotX. Help for the input of the various fields is available
inside the configuration tab itself.

The button 'Smart bonusfield' looks through the extrafields database and
detects the extrafields that could be added to the configuration. This last
feature only works in a (MySQL-)database PivotX.

Help for each field in a bonusfield is provided locally. Just select a field
and help is provided on the right side. Note that help for "Extra type 
information" is dependent on which "Type" is selected.

Smarty Tags
-----------

### getpagehierarchy ###

Returns a traversable object, which can be used to show a page hierarchy.

    [[getpagehierarchy assign=NAME root=ROOT searchroot=SEARCHROOT]]

All arguments are optional. Usually only 'root' or 'searchroot' is needed.

  * assign - assign result to this variable, defaults to 'root'
  * root - get the hierarchy using this 'uri' as root
  * searchroot - search for the root page of this 'uri'

Searchroot is handy when you are viewing a subpage (non-root) page and you
need to show the hierarchy from the root on.
The returned value is actually an object (bonusfieldsPage) with the page
information. You can retrieve regular page values using the "->" operator.
It contains several methods to traverse the hierarchy. See the example
below.

### getpagelistwithextrafields ###

Returns a list of page uri's where the extrafield EXTRAFIELDNAME has the
value VALUE.

    [[getpagelistwithextrafield assign=NAME extrafield=EXTRAFIELDNAME value=VALUE]]

Must specify 'extrafield' and 'value'.

  * assign - assign result to this variable, defaults to 'pagelist'
  * extrafield - name of the extrafield to compare to
  * value - value to compare the extrafield with

### getpagepath ###

Returns the pagepath for a certain page. Use this to show the breadcrumbs
of the page.

    [[getpagepath assign=NAME uri=URI]]

Only uri is required.

  * assign - assign result to this variable, defaults to 'pagepath'
  * uri - page uri to return the path of

Template Examples
-----------------

### Simple ###

Bonusfields only provides an interface to the PivotX-extrafields 
functionality. Showing an extrafield therefore is quite simple:

    [[$page.extrafields.myfield|escape]]

Simply replace $page by $entry if you need the data for an entry.

### Hierarchal pages ###

#### getpagehierarchy

If you want to use the hierarchal implementation you actually have to have
two templates at least. One to retrieve the hierarchy itself. This will be
just in your normal template where you want the hierarchy to show.
The other one is called for every *level* in the hierarchy.

    [[* file: my-template.tpl *]]
    
    [[getpagehierarchy assign='root' searchroot=$page.uri]]
    <ul class="menu">
        <li>
            [[include file="mytheme/my-pageleaf.tpl" page=$root]]
        </li>
    </ul>


    [[* file: my-pageleaf.tpl *]]

    <a href="[[$page->link]]">[[$page->title|escape]]</a>

    [[if $page->get_no_of_children()>0]]
    <ul>
		[[foreach from=$page->children item='child']]
		<li>
			[[include file="mytheme/my-pageleaf.tpl" page=$child]]
		</li>
		[[/foreach]]
    </ul>
    [[/if]]

#### getpagepath ####

Create simple breadcrumbs.

    [[pagepagepath uri=$page.uri]]

    <ul>
    [[foreach from=$pagepath item='pathpart']]
        [[getpage uri=$pathpart]]
        <li><a href="[[$page.link]]">[[$page.title|escape]]</a></li>
        [[resetpage]]
    [[/foreach]]
    </ul>

#### bonusgallery ####

Create a gallery object which can be looped.

##### Example #1 #####

Fancybox example using entry extrafield 'galleryimagelist'.

    [[*
        Smarty tag parameters:
        assign              assign the gallery to this variable
        content             contains the gallery text contents
        fancybox            if '1', tries to include the Fancybox javascript (need to have the extension)

        Image methods:
        $image->src         complete image source url
        $image->title       title
        $image->alt         alt
        $image->data        data
        $image->getImgSrc(width,height,options)
                            get timthumbed url using 'width' and 'height'
                            if no options are given 'zc=1' is assumed.
    *]]
    [[bonusgallery assign='gallery' content=$entry.extrafields.galleryimagelist fancybox='1']]
    [[foreach from=$gallery item='image']]
        <a href="[[$image->src]]" class="fancybox" title="[[$image->title|escape]]" rel="[[$image->rel]]">
            <img src="[[$image->getImgSrc(75,75)]]" alt="[[$image->alt|escape]]"
                title="[[$image->title|escape]]" />
        </a>
    [[/foreach]]


##### Example #2 #####

Show the titles of the images and prefix the title with number and total count, show the
image below that (600x120 zoom-cropped). Uses the page extrafield 'galleryimagelist'.

    [[*
        $gallery->number     inside the loop gives the image number which is shown
        $gallery->length     inside or outside the loops gives the number of images in the gallery
    *]]
    [[bonusgallery assign='gallery' content=$page.extrafields.galleryimagelist]]
    [[foreach from=$gallery item='image']]
        <h2>[[$gallery->number]]/[[$gallery->length]] [[$image->title|escape]]</h2>
        <img src="[[$image->getImgSrc(600,120)]]" alt="[[$image->alt|escape]]"
            title="[[$image->title|escape]]" data:info="[[$image->data|escape]]" />
    [[/foreach]]

#### bonusfieldinfo ####

Get the bonusfield configuration for a particular bonusfield. Mostly useful to get the
'options' list in the configuration.

    [[*
        Smarty tag parameters:
        assign              assign the bonusfieldinfo to this variable
        fieldkey            fieldkey to get
        contenttype         contenttype of fieldkey

        Methods:
        name                name of the field
        getOptions()        options as an Iterator or array
            .value          value of the option
            .label          label of the option
            .optgroup       if available, optgroup of the option
    *]]

    [[getbonusfieldinfo assign='news_category' fieldkey='news_category' contenttype='entry']]
    [[assign var='news_options' value=$news_category->getOptions()]]
	<ul>
    [[foreach from=$news_options item='news_option']]
		<li><a href="?news=[[$news_option.value|escape]]">[[$news_option.label|escape]]</a></li>
    [[/foreach]]
    </ul>

#### gettaxonomy ####

In case bonusfields is configured for a taxonomy this will allow you to get all the values.

    [[*
        Smarty tag parameters:
        assign          assign the values to this variable (defaults to name of taxonomy)
        taxonomy        name of the taxonomy to get
        contenttype     contenttype of the taxonomy
    *]]

    [[gettaxonomy taxonomy='subcategories' contenttype='entry']]
    <ul>
    [[foreach from=$subcategories item='subcategory']]
        <li><a href="?subcategory=[[$subcategory.value|escape]]">[[$subcategory.name|escape]]</a></li>
    [[/foreach]]
    </ul>
