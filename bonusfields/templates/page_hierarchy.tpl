[[include file="inc_header.tpl" ]]
	<style type="text/css">

	.page-hierarchy {
		position: relative;
    }

	.page-hierarchy a {
        text-decoration: none;
    }

	.page-hierarchy ul {
		list-style: none;
		margin: 0;
		padding: 0;
	}

	.page-hierarchy ul li {
		list-style: none !important;
		margin-left: 40px;
		font-family: Tahoma,Arial,sans-serif;
		font-size: 12px;
		line-height: 17px;
	}

    ul.hierarchy-1 {
        border: 1px solid #CCCCCC;
        border-top-left-radius: 3px;
        border-top-right-radius: 3px;
        margin-bottom: 20px;
	}

	.page-hierarchy ul.hierarchy-1 li {
        margin-left: 0px !important;
    }

	.page-hierarchy ul.hierarchy-2p li {
        margin-left: 40px !important;
        border-left: 1px solid #e8e8e8;
        font-size: 12px;
    }

	.page-hierarchy div.entriesclip {
		font-family: Tahoma,Arial,sans-serif;
		line-height: 17px;
		height: 44px;
    }

	.page-hierarchy ul.hierarchy-1 div.entriesclip strong a {
        font-size: 15px;
    }

	#fallen ul.hierarchy-1 div.entriesclip strong a {
        font-size: 12px;
    }

	.page-hierarchy ul.hierarchy-2p div.entriesclip strong a {
        font-size: 12px;
    }

	.page-hierarchy div.entriesclip .page {
        border-bottom: 1px solid #e8e8e8;
        height: 43px;
        padding: 0 4px;
	}

	.page-hierarchy div.odd {
		background-color: #f0f4ef;
        xbackground-color: #f5f5f5;
	}

	.page-hierarchy .entriesclip div.user {
		float: right;
		width: 170px;
		padding: 4px 2px 4px 8px;
	}

	.page-hierarchy .entriesclip div.buttons_small {
		float: right;
		width: 260px;
		padding: 4px 2px 4px 8px;
	}

	.page-hierarchy .entriesclip div.buttons_small a {
		margin-left: 10px;
	}

	</style>

	[[assign var='oddoreven' value='even']]
	<div class="page-hierarchy" id="hierarchy">
		[[foreach from=$roots item='root']]
		<ul class="hierarchy-1">
			<li>
				[[include file="../extensions/bonusfields/templates/hierarchy_leaf.tpl" page=$root]]
			</li>
		</ul>
		[[/foreach]]
	</div>

    <div class="page-hierarchy" id="fallen">
        <ul class="hierarchy-1">
        [[foreach from=$fallen item='leaf']]
			<li>
				[[include file="../extensions/bonusfields/templates/hierarchy_leaf.tpl" page=$leaf]]
			</li>
        [[/foreach]]
        </ul>
    </div>

<br />

[[include file="inc_footer.tpl" ]]
