<!-- Default template for the shop checkout pages -->
<!-- This template is based on skinny/page_template.html -->
<!-- It should include title and body snippets -->
[[ include file="`$templatedir`/_sub_header.html" ]]
	
	<div id="content">
	
		<div id="main">

			<div class="entry">
				<h2><a href="[[ link hrefonly=1 ]]">[[ title ]]</a></h2>

				[[ body ]]

			</div><!-- .entry -->

		</div><!-- #main -->
	
		[[ include file="`$templatedir`/_sub_sidebar.html" ]]

	</div><!-- #content -->
	
[[ include file="`$templatedir`/_sub_footer.html" ]]
