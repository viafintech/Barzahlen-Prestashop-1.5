{capture name=path}{l s='Barzahlen' mod='barzahlen'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='barzahlen'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if isset($nbProducts) &&  $nbProducts <= 0}
  <p class="warning">{l s='Your shopping cart is empty.' mod='barzahlen'}</p>
{else}

<h3>{l s='Barzahlen' mod='barzahlen'}</h3>
<form action="{$link->getModuleLink('barzahlen', 'validation', [], true)}" method="post">

<img src="https://cdn.barzahlen.de/images/barzahlen_logo.png" alt="{l s='Barzahlen' mod='barzahlen'}"/><br/><br/>
<p>{l s='After completing your order you get a payment slip from Barzahlen that you can easily print out or have it sent via SMS to your mobile phone. With the help of that payment slip you can pay your online purchase at one of our retail partners (e.g. supermarket).' mod='barzahlen'}</p>
{if $barzahlen_sandbox}
  <p>{l s='The <strong>Sandbox Mode</strong> is active. All placed orders receive a test payment slip. Test payment slips cannot be handled by our retail partners.' mod='barzahlen'}</p>
{/if}
<strong>{l s='Pay at:' mod='barzahlen'}</strong>&nbsp;
<img src="https://cdn.barzahlen.de/images/barzahlen_partner_01.png" alt="" style="vertical-align: middle; height: 25px;" />
<img src="https://cdn.barzahlen.de/images/barzahlen_partner_02.png" alt="" style="vertical-align: middle; height: 25px;" />
<img src="https://cdn.barzahlen.de/images/barzahlen_partner_03.png" alt="" style="vertical-align: middle; height: 25px;" />
<img src="https://cdn.barzahlen.de/images/barzahlen_partner_04.png" alt="" style="vertical-align: middle; height: 25px;" />
<img src="https://cdn.barzahlen.de/images/barzahlen_partner_05.png" alt="" style="vertical-align: middle; height: 25px;" />
<img src="https://cdn.barzahlen.de/images/barzahlen_partner_06.png" alt="" style="vertical-align: middle; height: 25px;" />
<img src="https://cdn.barzahlen.de/images/barzahlen_partner_07.png" alt="" style="vertical-align: middle; height: 25px;" />
<img src="https://cdn.barzahlen.de/images/barzahlen_partner_08.png" alt="" style="vertical-align: middle; height: 25px;" />
<img src="https://cdn.barzahlen.de/images/barzahlen_partner_09.png" alt="" style="vertical-align: middle; height: 25px;" />
<img src="https://cdn.barzahlen.de/images/barzahlen_partner_10.png" alt="" style="vertical-align: middle; height: 25px;" />
<p class="cart_navigation">
	<input type="submit" name="submit" value="{l s='I confirm my order' mod='barzahlen'}" class="exclusive_large" />
	<a href="{$link->getPageLink('order', true, NULL, "step=3")}" class="button_large">{l s='Other payment methods' mod='barzahlen'}</a>
</p>
</form>
{/if}
