{* this div is being used to apply special css *}
{if $section eq 1}
    <div class="crm-block crm-content-block crm-report-layoutGraph-form-block">
        {*include the graph*}
        {include file="CRM/Report/Form/Layout/Graph.tpl"}
    </div>
{elseif $section eq 2}
    <div class="crm-block crm-content-block crm-report-layoutTable-form-block">
        {*include the table layout*}
        {include file="CRM/Report/Form/Layout/Table.tpl"}
    </div>
{else}
    {if $criteriaForm OR $instanceForm OR $instanceFormError}
        <div class="crm-block crm-form-block crm-report-field-form-block">
            {include file="CRM/Report/Form/Fields.tpl"}
        </div>
    {/if}
    <div class="crm-block crm-content-block crm-report-form-block">
        {*include actions*}
        {include file="CRM/Report/Form/Actions.tpl"}


        {*include the graph*}
        {include file="CRM/Report/Form/Layout/Graph.tpl"}

        {*include the table layout*}
        {*include file="CRM/Report/Form/Layout/Table.tpl"*}
        {assign var="bemasTableStyle" value="border-collapse: collapse; font-family: Arial Narrow"}
        {assign var="bemasColHeaderStyle" value="text-align: left; border-top: 1px solid #000000; border-bottom: 1px solid #000000; padding-left: 10px; padding-right: 10px; background-color: #1a6c86; color: #FFFFFF"}
        {assign var="bemasCellStyle" value="border-bottom: 1px solid #000000; padding-left: 10px; padding-right: 10px;"}

        {include file="CRM/Bemasreporting/Form/Report/PresenceListRows.tpl"}

        <br />

        {include file="CRM/Report/Form/ErrorMessage.tpl"}
    </div>
{/if}
{if $outputMode == 'print'}
    <script type="text/javascript">
      window.print();
    </script>
{/if}

<!-- footer on every page -->
<div style="position: fixed; left: 0; bottom: -1.6em; font-size: 10px; line-height: 1.8;">
    <p>{$eventDate} - {$eventTitle}</p>
</div>