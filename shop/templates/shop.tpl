<!-- Default template for the shop weblog pages -->
<!-- This template is based on skinny/frontpage_template.html -->
<!-- You can use the shop template in a weblog that shows the default shop category -->
<!-- It should include an addtocart snippet if the shop is not configured to automatically add those -->
[[ include file="`$templatedir`/_sub_header.html" ]]
	
	<div id="content">
	
		<div id="main">
		
			<!-- begin of weblog 'standard' -->
			[[ subweblog name="standard" ]][[ literal ]]
		
			<div class="entry">
				<h2><a href="[[ link hrefonly=1 ]]">[[ title ]]</a></h2>
				<h3>[[ subtitle ]]</h3>
				[[ introduction ]]
				[[ pricedisplay entry=$entry ]]
				[[ addtocart entry=$entry ]]
				<p>[[ more ]]</p>
				<div class="meta">
					[[ user field=emailtonick ]] | [[ date format="%dayname% %day% %monthname% %year% at %hour12%&#58;%minute% %ampm%" ]] | 
					[[ permalink text="&para;" title="Permanent link to '%title%' in the archives" ]] |
					[[ category link=true ]] | 
					[[ commentlink ]]
                    [[ editlink format="Edit" prefix=" | " ]]
				</div>
				<div class="meta">
					[[ tags ]]
            	
				</div>
			</div>
			
			[[ /literal ]][[ /subweblog ]]
			<!-- end of weblog 'standard' -->
			
			<div class="pagenav">
				[[ paging action="prev" ]] |
				[[ paging action="curr" ]] |
				[[ paging action="next" ]]
			</div>
		</div><!-- #main -->
	
		[[ include file="`$templatedir`/_sub_sidebar.html" ]]
		
	</div><!-- #content -->
	
[[ include file="`$templatedir`/_sub_footer.html" ]]
