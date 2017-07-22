<div class="product-details clearfix">

	<div class="row">
		<div class="col-md-6">

			<div class="product-status product-status-{$rawstatus|strtolower}">
				<div class="product-icon text-center">
					<span class="fa-stack fa-lg">
						<i class="fa fa-circle fa-stack-2x"></i>
						<i class="fa fa-{if $type eq "hostingaccount" || $type == "reselleraccount"}hdd-o{elseif $type eq "server"}database{else}archive{/if} fa-stack-1x fa-inverse"></i>
					</span>
					<h3>{$product}</h3>
					<h4>{$groupname}</h4>
				</div>
				<div class="product-status-text">
					{$status}
				</div>
			</div>

			{if $showcancelbutton || $packagesupgrade}
				<div class="row" style="margin: 0px 0 8px 0">
					{if $packagesupgrade}
						<div class="col-xs-{if $showcancelbutton}6{else}12{/if}">
							<a href="upgrade.php?type=package&amp;id={$id}" class="btn btn-block btn-success">{$LANG.upgrade}</a>
						</div>
					{/if}
					{if $showcancelbutton}
						<div class="col-xs-{if $packagesupgrade}6{else}12{/if}">
							<a href="clientarea.php?action=cancel&amp;id={$id}" class="btn btn-block btn-danger {if $pendingcancellation}disabled{/if}">{if $pendingcancellation}{$LANG.cancellationrequested}{else}{$LANG.clientareacancelrequestbutton}{/if}</a>
						</div>
					{/if}
				</div>
			{/if}
			<div class="row">
				<div class="col-xs-12">
					<a href="{$panelhostname}" class="btn btn-block btn-success">Go to User Panel</a>
				</div>
			</div>

		</div>
		<div class="col-md-6 text-center">

			<h4>{$LANG.clientareahostingregdate}</h4>
			{$regdate}

			{if $firstpaymentamount neq $recurringamount}
				<h4>{$LANG.firstpaymentamount}</h4>
				{$firstpaymentamount}
			{/if}

			{if $billingcycle != $LANG.orderpaymenttermonetime && $billingcycle != $LANG.orderfree}
				<h4>{$LANG.recurringamount}</h4>
				{$recurringamount}
			{/if}

			<h4>{$LANG.orderbillingcycle}</h4>
			{$billingcycle}

			<h4>{$LANG.clientareahostingnextduedate}</h4>
			{$nextduedate}

			<h4>{$LANG.orderpaymentmethod}</h4>
			{$paymentmethod}

			{if $suspendreason}
				<h4>{$LANG.suspendreason}</h4>
				{$suspendreason}
			{/if}

		</div>
	</div>

</div>

{foreach $hookOutput as $output}
	<div>
		{$output}
	</div>
{/foreach}

	<div class="row clearfix">
		<div class="col-xs-12">
			<ul class="nav nav-tabs nav-tabs-overflow">
					<li class="active">
						<a href="#manage" data-toggle="tab"><i class="fa fa-globe fa-fw"></i> {$LANG.manage}</a>
					</li>
				{if $configurableoptions}
					<li{if !$domain && !$moduleclientarea} class="active"{/if}>
						<a href="#configoptions" data-toggle="tab"><i class="fa fa-cubes fa-fw"></i> {$LANG.orderconfigpackage}</a>
					</li>
				{/if}
				{if $customfields}
					<li{if !$domain && !$moduleclientarea && !$configurableoptions} class="active"{/if}>
						<a href="#additionalinfo" data-toggle="tab"><i class="fa fa-info fa-fw"></i> {$LANG.additionalInfo}</a>
					</li>
				{/if}
				{if $lastupdate}
					<li{if !$domain && !$moduleclientarea && !$configurableoptions && !$customfields} class="active"{/if}>
						<a href="#resourceusage" data-toggle="tab"><i class="fa fa-inbox fa-fw"></i> {$LANG.resourceUsage}</a>
					</li>
				{/if}
			</ul>
		</div>
	</div>
	<div class="tab-content product-details-tab-container">
			<div class="tab-pane fade{if !$domain} in active{/if} text-center" id="manage">
					<div class="text-center module-client-area">
						<div class="row">
							<div class="col-md-6">
								<h4>Login Email: <h4>
								{$email}
								<h4>Login URL: </h4>
								<a href="{$panelhostname}">{$panelhostname}</a>
							</div>
						<div>
					</div>
			</div>
		{if $configurableoptions}
			<div class="tab-pane fade{if !$domain && !$moduleclientarea} in active{/if} text-center" id="configoptions">
				{foreach from=$configurableoptions item=configoption}
					<div class="row">
						<div class="col-sm-5">
							<strong>{$configoption.optionname}</strong>
						</div>
						<div class="col-sm-7 text-left">
							{if $configoption.optiontype eq 3}{if $configoption.selectedqty}{$LANG.yes}{else}{$LANG.no}{/if}{elseif $configoption.optiontype eq 4}{$configoption.selectedqty} x {$configoption.selectedoption}{else}{$configoption.selectedoption}{/if}
						</div>
					</div>
				{/foreach}
			</div>
		{/if}
		{if $customfields}
			<div class="tab-pane fade{if !$domain && !$moduleclientarea && !$configurableoptions} in active{/if} text-center" id="additionalinfo">
				{foreach from=$customfields item=field}
					<div class="row">
						<div class="col-sm-5">
							<strong>{$field.name}</strong>
						</div>
						<div class="col-sm-7 text-left">
							{$field.value}
						</div>
					</div>
				{/foreach}
			</div>
		{/if}
		{if $lastupdate}
			<div class="tab-pane fade text-center" id="resourceusage">
				<div class="col-sm-10 col-sm-offset-1">
					<div class="col-sm-6">
						<h4>{$LANG.diskSpace}</h4>
						<input type="text" value="{$diskpercent|substr:0:-1}" class="dial-usage" data-width="100" data-height="100" data-min="0" data-readOnly="true" />
						<p>{$diskusage}MB / {$disklimit}MB</p>
					</div>
					<div class="col-sm-6">
						<h4>{$LANG.bandwidth}</h4>
						<input type="text" value="{$bwpercent|substr:0:-1}" class="dial-usage" data-width="100" data-height="100" data-min="0" data-readOnly="true" />
						<p>{$bwusage}MB / {$bwlimit}MB</p>
					</div>
				</div>
				<div class="clearfix">
				</div>
				<p class="text-muted">{$LANG.clientarealastupdated}: {$lastupdate}</p>

				<script src="{$BASE_PATH_JS}/jquery.knob.js"></script>
				<script type="text/javascript">
				jQuery(function() {ldelim}
					jQuery(".dial-usage").knob({ldelim}'format':function (v) {ldelim} alert(v); {rdelim}{rdelim});
				{rdelim});
				</script>
			</div>
		{/if}
	</div>
