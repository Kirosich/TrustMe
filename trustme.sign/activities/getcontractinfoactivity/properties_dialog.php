<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

// Загружаем языковые файлы для properties_dialog
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

/** @var array $arCurrentValues */
/** @var string $formName */

$apiToken = isset($arCurrentValues['ApiToken']) ? $arCurrentValues['ApiToken'] : '';
$testMode = isset($arCurrentValues['TestMode']) ? $arCurrentValues['TestMode'] : 'Y';
$contractId = isset($arCurrentValues['ContractId']) ? $arCurrentValues['ContractId'] : '';
$dealId = isset($arCurrentValues['DealId']) ? $arCurrentValues['DealId'] : '';

?>
<tr>
    <td align="right" width="40%">
        <span class="adm-required-field">
            <?= GetMessage('CBP_TRUSTME_API_TOKEN') ?>:
        </span>
    </td>
    <td width="60%">
        <?= CBPDocument::ShowParameterField(
            'string',
            'ApiToken',
            $apiToken,
            array('size' => 80, 'id' => 'id_ApiToken')
        ); ?>
        <br>
        <small><?= GetMessage('CBP_TRUSTME_API_TOKEN_DESC') ?></small>
    </td>
</tr>
<tr>
    <td align="right" width="40%">
        <?= GetMessage('CBP_TRUSTME_TEST_MODE') ?>:
    </td>
    <td width="60%">
        <select name="TestMode" id="id_TestMode">
            <option value="Y" <?= $testMode === 'Y' ? 'selected' : '' ?>>
                <?= GetMessage('CBP_TRUSTME_YES') ?>
            </option>
            <option value="N" <?= $testMode === 'N' ? 'selected' : '' ?>>
                <?= GetMessage('CBP_TRUSTME_NO') ?>
            </option>
        </select>
        <br>
        <small><?= GetMessage('CBP_TRUSTME_TEST_MODE_DESC') ?></small>
    </td>
</tr>
<tr>
    <td colspan="2">
        <b><?= GetMessage('CBP_TRUSTME_GETINFO_SECTION_CONTRACT') ?></b>
    </td>
</tr>
<tr>
    <td align="right" width="40%">
        <span class="adm-required-field">
            <?= GetMessage('CBP_TRUSTME_GETINFO_CONTRACT_ID') ?>:
        </span>
    </td>
    <td width="60%">
        <?= CBPDocument::ShowParameterField(
            'string',
            'ContractId',
            $contractId,
            array('size' => 50, 'id' => 'id_ContractId')
        ); ?>
        <br>
        <small><?= GetMessage('CBP_TRUSTME_GETINFO_CONTRACT_ID_DESC') ?></small>
    </td>
</tr>
<tr>
    <td align="right" width="40%">
        <span class="adm-required-field">
            <?= GetMessage('CBP_TRUSTME_GETINFO_DEAL_ID') ?>:
        </span>
    </td>
    <td width="60%">
        <?= CBPDocument::ShowParameterField(
            'string',
            'DealId',
            $dealId,
            array('size' => 20, 'id' => 'id_DealId')
        ); ?>
        <br>
        <small><?= GetMessage('CBP_TRUSTME_GETINFO_DEAL_ID_DESC') ?></small>
    </td>
</tr>
