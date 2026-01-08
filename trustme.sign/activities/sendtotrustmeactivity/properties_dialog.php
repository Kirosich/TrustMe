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
$documentUrl = isset($arCurrentValues['DocumentUrl']) ? $arCurrentValues['DocumentUrl'] : '';
$contractName = isset($arCurrentValues['ContractName']) ? $arCurrentValues['ContractName'] : '';
$numberDial = isset($arCurrentValues['NumberDial']) ? $arCurrentValues['NumberDial'] : '';
$signerFio = isset($arCurrentValues['SignerFio']) ? $arCurrentValues['SignerFio'] : '';
$signerIin = isset($arCurrentValues['SignerIin']) ? $arCurrentValues['SignerIin'] : '';
$signerPhone = isset($arCurrentValues['SignerPhone']) ? $arCurrentValues['SignerPhone'] : '';
$signerCompany = isset($arCurrentValues['SignerCompany']) ? $arCurrentValues['SignerCompany'] : '';
$additionalInfo = isset($arCurrentValues['AdditionalInfo']) ? $arCurrentValues['AdditionalInfo'] : '';
$kzBmg = isset($arCurrentValues['KzBmg']) ? $arCurrentValues['KzBmg'] : 'N';
$faceId = isset($arCurrentValues['FaceId']) ? $arCurrentValues['FaceId'] : 'N';

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
        <b><?= GetMessage('CBP_TRUSTME_SECTION_DOCUMENT') ?></b>
    </td>
</tr>
<tr>
    <td align="right" width="40%">
        <span class="adm-required-field">
            <?= GetMessage('CBP_TRUSTME_DOCUMENT_URL') ?>:
        </span>
    </td>
    <td width="60%">
        <?= CBPDocument::ShowParameterField(
            'string',
            'DocumentUrl',
            $documentUrl,
            array('size' => 80, 'id' => 'id_DocumentUrl')
        ); ?>
        <br>
        <small><?= GetMessage('CBP_TRUSTME_DOCUMENT_URL_DESC') ?></small>
    </td>
</tr>
<tr>
    <td align="right" width="40%">
        <span class="adm-required-field">
            <?= GetMessage('CBP_TRUSTME_CONTRACT_NAME') ?>:
        </span>
    </td>
    <td width="60%">
        <?= CBPDocument::ShowParameterField(
            'string',
            'ContractName',
            $contractName,
            array('size' => 50, 'id' => 'id_ContractName')
        ); ?>
        <br>
        <small><?= GetMessage('CBP_TRUSTME_CONTRACT_NAME_DESC') ?></small>
    </td>
</tr>
<tr>
    <td align="right" width="40%">
        <?= GetMessage('CBP_TRUSTME_NUMBER_DIAL') ?>:
    </td>
    <td width="60%">
        <?= CBPDocument::ShowParameterField(
            'string',
            'NumberDial',
            $numberDial,
            array('size' => 30, 'id' => 'id_NumberDial')
        ); ?>
        <br>
        <small><?= GetMessage('CBP_TRUSTME_NUMBER_DIAL_DESC') ?></small>
    </td>
</tr>
<tr>
    <td colspan="2">
        <b><?= GetMessage('CBP_TRUSTME_SECTION_SIGNER') ?></b>
    </td>
</tr>
<tr>
    <td align="right" width="40%">
        <span class="adm-required-field">
            <?= GetMessage('CBP_TRUSTME_SIGNER_FIO') ?>:
        </span>
    </td>
    <td width="60%">
        <?= CBPDocument::ShowParameterField(
            'string',
            'SignerFio',
            $signerFio,
            array('size' => 50, 'id' => 'id_SignerFio')
        ); ?>
        <br>
        <small><?= GetMessage('CBP_TRUSTME_SIGNER_FIO_DESC') ?></small>
    </td>
</tr>
<tr>
    <td align="right" width="40%">
        <span class="adm-required-field">
            <?= GetMessage('CBP_TRUSTME_SIGNER_IIN') ?>:
        </span>
    </td>
    <td width="60%">
        <?= CBPDocument::ShowParameterField(
            'string',
            'SignerIin',
            $signerIin,
            array('size' => 20, 'id' => 'id_SignerIin')
        ); ?>
        <br>
        <small><?= GetMessage('CBP_TRUSTME_SIGNER_IIN_DESC') ?></small>
    </td>
</tr>
<tr>
    <td align="right" width="40%">
        <span class="adm-required-field">
            <?= GetMessage('CBP_TRUSTME_SIGNER_PHONE') ?>:
        </span>
    </td>
    <td width="60%">
        <?= CBPDocument::ShowParameterField(
            'string',
            'SignerPhone',
            $signerPhone,
            array('size' => 20, 'id' => 'id_SignerPhone')
        ); ?>
        <br>
        <small><?= GetMessage('CBP_TRUSTME_SIGNER_PHONE_DESC') ?></small>
    </td>
</tr>
<tr>
    <td align="right" width="40%">
        <?= GetMessage('CBP_TRUSTME_SIGNER_COMPANY') ?>:
    </td>
    <td width="60%">
        <?= CBPDocument::ShowParameterField(
            'string',
            'SignerCompany',
            $signerCompany,
            array('size' => 50, 'id' => 'id_SignerCompany')
        ); ?>
        <br>
        <small><?= GetMessage('CBP_TRUSTME_SIGNER_COMPANY_DESC') ?></small>
    </td>
</tr>
<tr>
    <td colspan="2">
        <b><?= GetMessage('CBP_TRUSTME_SECTION_OPTIONS') ?></b>
    </td>
</tr>
<tr>
    <td align="right" width="40%">
        <?= GetMessage('CBP_TRUSTME_ADDITIONAL_INFO') ?>:
    </td>
    <td width="60%">
        <?= CBPDocument::ShowParameterField(
            'text',
            'AdditionalInfo',
            $additionalInfo,
            array('rows' => 3, 'cols' => 50, 'id' => 'id_AdditionalInfo')
        ); ?>
    </td>
</tr>
<tr>
    <td align="right" width="40%">
        <?= GetMessage('CBP_TRUSTME_KZ_BMG') ?>:
    </td>
    <td width="60%">
        <select name="KzBmg" id="id_KzBmg">
            <option value="N" <?= $kzBmg === 'N' ? 'selected' : '' ?>>
                <?= GetMessage('CBP_TRUSTME_NO') ?>
            </option>
            <option value="Y" <?= $kzBmg === 'Y' ? 'selected' : '' ?>>
                <?= GetMessage('CBP_TRUSTME_YES') ?>
            </option>
        </select>
    </td>
</tr>
<tr>
    <td align="right" width="40%">
        <?= GetMessage('CBP_TRUSTME_FACE_ID') ?>:
    </td>
    <td width="60%">
        <select name="FaceId" id="id_FaceId">
            <option value="N" <?= $faceId === 'N' ? 'selected' : '' ?>>
                <?= GetMessage('CBP_TRUSTME_NO') ?>
            </option>
            <option value="Y" <?= $faceId === 'Y' ? 'selected' : '' ?>>
                <?= GetMessage('CBP_TRUSTME_YES') ?>
            </option>
        </select>
    </td>
</tr>
