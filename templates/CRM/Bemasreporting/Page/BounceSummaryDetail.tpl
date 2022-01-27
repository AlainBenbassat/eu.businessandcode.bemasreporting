<h3>Bounces: {$filter_description}</h3>

<table id="bounce-detail" class="display">
  <thead>
  <tr>
    <th>ID</th>
    <th>Voornaam</th>
    <th>Naam</th>
    <th>Organisatie</th>
    <th>E-mail</th>
    <th>Bounce datum</th>
  </tr>
  </thead>
    {foreach from=$rows item=row}
      <tr class="crm-entity">
        <td><a href="contact/view?reset=1&cid={$row.id}">{$row.id}</a></td>
        <td>{$row.first_name}</td>
        <td>{$row.last_name}</td>
        <td>{$row.organization_name}</td>
        <td>{$row.email}</td>
        <td>{$row.hold_date}</td>
      </tr>
    {/foreach}
</table>
