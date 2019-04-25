
{* Example: Display a variable directly *}
<h3>Duplicates</h3>
<table>
  <tr>
  <th>Address</th>
  <th>Names</th>
  <th>Count</th>
  </tr>
{foreach from=$duplicates key=address item=duplicate_names}
  <tr>
    <td>{$address}</td>
    <td>
      <ul>
      {foreach from=$duplicate_names item=name}
        <li>{$name}</li>
      {/foreach}
      </ul>
    </td>
    <td>{$duplicate_names|@count}</td>
  </tr>
{/foreach}
</table>


<h3>Postal Code Counts</h3>

<table style="width:20em;">
{foreach from=$zipcounts key=zip item=count}
  <tr>
    <td>{$zip}</td>
    <td>{$count}</td>
  </tr>
{/foreach}
  <tr>
    <td><strong>Total</strong></td>
    <td><strong>{$total}</strong></td>
  </tr>
</table>


