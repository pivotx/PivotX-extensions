	<div class="entriesclip [[oddoreven]]">
		<div class="buttons_small">
			<a href="index.php?page=page&amp;uid=[[$page->uid]]"><img alt="" src="./pics/page_white_edit.png">[[t]]Edit Page[[/t]]</a>
			<a class="negative" href="#" onclick="return confirmme('index.php?page=hierarchy&amp;delpage=[[ $uid ]]', '[[t escape=js ]]Are you sure you wish to delete this Page?[[/t]]');"><img alt="" src="./pics/page_white_delete.png">[[t]]Delete Page[[/t]]</a>
		</div>

		<div class="user">
            <span>
                [[ assign var=username value=$page->user ]]
                [[ if $users.$username != "" ]][[ $users.$username]][[ else ]][[ $page->user]][[/if]],  
                [[ if $page->status=="publish" ]][[ date date=$page->date format="%day%-%month%-'%ye% %hour24%:%minute%" ]][[else]]-[[/if]]
            </span><br />
            <span style="font-size: 85%;">
                [[ if $page->template!="-"]]
                    [[ $page->template|truncate:35 ]]
                [[else]]
                    [[* <em>([[t]]default template[[/t]])</em> *]]
                [[/if]]
            </span>
		</div>

		<div class="page">
			&#8470; [[$page->uid]]
			<strong>
				<a href="index.php?page=page&amp;uid=[[$page->uid]]">[[$page->title|truncate:35]]</a>
			</strong>

			<span style="color:#555; font-size: 85%;">
				([[$page->uri]] - [[t]]order[[/t]] [[ $page->sortorder ]]
                [[ if $page->status=="publish" ]]- <a href="[[$page->link]]" class="front_end"><strong>[[t]]Published[[/t]]</strong></a>)[[/if]]
				[[ if $page->status=="timed" ]]- <strong>[[t]]Timed Publish[[/t]])</strong>[[/if]]
				[[ if $page->status=="hold" ]]- <strong>[[t]]Hold[[/t]])</strong>[[/if]]
            </span><br />
			<div class="clip" style="width: 500px;">[[ $page->introduction|hyphenize ]]</div>
		</div>
	</div>

	[[if $page->get_no_of_children()>0]]
	<ul class="hierarchy-2p">
		[[foreach from=$page->children item='child']]
		<li>
			[[include file="../extensions/bonusfields/templates/hierarchy_leaf.tpl" page=$child]]
		</li>
		[[/foreach]]
	</ul>
	[[/if]]
