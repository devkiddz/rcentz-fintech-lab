<?php
$riskPosition = $riskPosition ?? null;
$usePrice = $riskPosition && ($riskPosition->stop_loss_price !== null || $riskPosition->take_profit_price !== null)
    && $riskPosition->stop_loss_percent === null && $riskPosition->take_profit_percent === null;
$riskMode = old('risk_mode', $usePrice ? 'price' : 'percent');
if (!$allowPrices) { $riskMode = 'percent'; }
?>
<div data-risk-inputs>
    <label class="ui-label">Stop loss / take profit basis
        <select name="risk_mode" data-risk-mode class="ui-input mt-1 w-full">
            <option value="percent" <?php if ($riskMode === 'percent') { echo 'selected'; } ?>>Percentage from entry</option>
            <?php if ($allowPrices): ?>
            <option value="price" <?php if ($riskMode === 'price') { echo 'selected'; } ?>>Exact market price</option>
            <?php endif; ?>
        </select>
    </label>
    <?php foreach (['percent', 'price'] as $basis): ?>
    <fieldset data-risk-basis="<?= e($basis) ?>" class="mt-3 grid grid-cols-2 gap-3" <?php if ($riskMode !== $basis) { echo 'disabled hidden style="display:none"'; } ?>>
        <?php foreach (['stop_loss'=>'Stop loss', 'take_profit'=>'Take profit'] as $key=>$label):
            $field = $key.'_'.$basis;
            $value = old($field, $riskPosition ? $riskPosition->{$field} : null);
        ?>
        <label class="ui-label"><?= e($label) ?> <?= $basis === 'percent' ? '%' : 'price' ?>
            <input class="ui-input mt-1 w-full" name="<?= e($field) ?>" type="number"
                step="<?= $basis === 'percent' ? '0.01' : '0.00000001' ?>" min="<?= $basis === 'percent' ? '0.01' : '0.00000001' ?>"
                max="<?= $basis === 'percent' ? '99.99' : '1000000000000' ?>"
                value="<?= e($value ?? '') ?>" placeholder="Optional">
        </label>
        <?php endforeach; ?>
    </fieldset>
    <?php endforeach; ?>
    <p class="mt-2 text-[10px] leading-4 text-muted-foreground">Exact prices use the instrument’s quote currency. Long: stop below entry, target above. Short: stop above entry, target below.</p>
</div>
