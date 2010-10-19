# Changelog

*   ## Version 0.25
	2010-10-19

	*   added database logging support (by popular demand) still needs admin interface and better examples
    * improved documentation
*   ## Version 0.24
	2010-09-24

	*   added support for custom id's in fieldsets
*   ## Version 0.23
	2010-09-21

	*   added extra protection against parsing the wrong forms
	*   added tabindex improvements (tnx marcel)
*   ## Version 0.22
	2010-08-03
	
	*   added gettext to default templates
	*   added pre_html and post_html to all short formbuilder tags
	*   modified sendtofriend, changed defaults handling
	*   modified orderform, added phone number field
	*   removed the type labels from the replacement variables in the confirmation templates
*   ## Version 0.21
	2010-05-28
	
	*   Updated documentation to markdown
*   ## Version 0.20
	2010-05-27

    *	New Feature: [[sendtofriend]] form
    *	Removed krumo - don't use it anymore, and it cleans up the source.
    *	Refactored recipient, sender, cc and bcc processing to make it uniform and enable the sendtofriend.
    *	Updated de documentation.
*   ## Version 0.19
	2010-05-03

    *	Improvements for the [[formbuilder]] tag.
    *	moved FAQ in documentation to a separate page
*   ## Version 0.18
	2010-04-12

    *	added new [[formbuilder]] block tag
*   ## Version 0.17
	2010-03-22

    *	updated docs to new standards
*   ## Version 0.16
	2010-03-10

    *	step one for new [[formbuilder]] block tag
*   ## Version 0.15
	2010-03-10

    *	Made javascript mandatory for form submissions.
    *	Added crude spam detection - will only work against dumb js-less bots.
*   ## Version 0.14
	2010-01-29

    *	Added readonly fields to the documentation and fixed the html default for readonly fields.
*   ## Version 0.13
	2010-01-26

    *	Fixed double submission when the same or a similar form was present in the rendered template.
    *	Updated spacing for more conformance to coding guidelines.
    *	Included typenames in the default templates, this makes customizing easier.
*   ## Version 0.12
	2010-01-18

    *	Repaired file upload function thanks to @petervangrieken and @marcelfw
*   ## Version 0.11
	2009-12-30

*   ## Version 0.10
	2009-12-22

    *	Added check for email recipient when showinweblog is on + documentation
    *	Moved the logic for the basic recipient around to make it less ambigouous
*   ## Version 0.9 : Documentation update
	2009-12-18

    *	Updated documentation with an extra example for contactform
*   ## Version 0.8 : Cosmetic update & bugfixes
	2009-12-02

    *	Fixed: email subject was not used when set
    *	Changed spacing to conform to guidelines
    *	Updated documentation style
*   ## Version 0.7 : Public release
	2009-11-23

    *	Updated documentation to conform to guidelines
*   ## Version 0.1 - Version 0.6 : Initial release - Private Testversions
	2009-10-01 - 2009-10-20

    *	Initial release as a plugin for PivotX
