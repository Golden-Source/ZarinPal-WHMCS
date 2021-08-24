<h3>تراکنش ها</h3>

<?php

use WHMCSZarinpal\Enum\StatusEnum;

$statusBadgeColors = [
    StatusEnum::PENDING   => 'inactive',
    StatusEnum::SUCCESS   => 'active',
    StatusEnum::FAILED    => 'inactive',
    StatusEnum::CANCELLED => 'inactive',
];

$statusTransaction = [
    StatusEnum::PENDING   => 'نامعلوم',
    StatusEnum::SUCCESS   => 'موفق',
    StatusEnum::FAILED    => 'ناموفق',
    StatusEnum::CANCELLED => 'لغو شده',
];

?>

<form method="post" action="addonmodules.php?module=ZarinpalAddon&tab=transactions">
    <div class="col-xs-12">
        <ul class="nav nav-tabs admin-tabs" role="tablist">
            <li class="<?= (isset($_REQUEST['search']) ? 'active' : null); ?>">
                <a class="tab-top" href="#search-filter-tab" role="tab" data-toggle="tab" id="tabLink1" data-tab-id="1"
                   aria-expanded="true">
                    جستجو / فیلتر
                </a>
            </li>
        </ul>
        <div class="tab-content admin-tabs">
            <div class="tab-pane search-box <?= (isset($_REQUEST['search']) ? 'active' : null); ?>"
                 id="search-filter-tab">
                <div class="search-box--form-inline">
                    <div class="form-group row col-xs-12 col-md-6">
                        <label for="search-uuid" class="col-xs-12 col-sm-3 col-form-label">Authority</label>
                        <div class="col-xs-12 col-sm-9">
                            <input type="text" class="form-control" id="search-uuid" name="search[authority]"
                                   value="<?= ($vars['search']['authority'] ?? null); ?>"/>
                        </div>
                    </div>
                    <div class="form-group row col-xs-12 col-md-6">
                        <label for="search-invoiceid"
                               class="col-xs-12 col-sm-3 col-form-label">شماره فاکتور</label>
                        <div class="col-sm-3">
                            <input type="number" class="form-control" id="search-invoiceid" name="search[invoice_id]"
                                   value="<?= ($vars['search']['invoice_id'] ?? null); ?>"/>
                        </div>
                    </div>
                    <div class="form-group row col-xs-12 col-md-6">
                        <label for="search-ip"
                               class="col-xs-12 col-sm-3 col-form-label">ای پی</label>
                        <div class="col-xs-12 col-sm-5">
                            <input type="text" class="form-control" pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$"
                                   id="search-ip"
                                   name="search[ip]" value="<?= ($vars['search']['ip'] ?? null); ?>"/>
                        </div>
                    </div>
                </div>
                <div class="search-box--buttons-container">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-search"></i>
                        جستجو
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<br/>

<table class="datatable" width="100%">
    <tr>
        <th class="text-center">#</th>
        <th class="text-center">کاربر</th>
        <th class="text-center">فاکتور</th>
        <th class="text-center">مبلغ</th>
        <th class="text-center">وضعیت</th>
        <th class="text-center">ای پی</th>
        <th class="text-center">شماره کارت</th>
        <th class="text-center">تاریخ ایجاد</th>
        <th class="text-center">آخرین بروزرسانی</th>
    </tr>
    <?php foreach ($vars['transactions'] as $transaction): ?>
        <tr>
            <td class="text-center"><?= $transaction->id; ?></td>
            <td class="text-center">
                <a target="_blank"
                   href="clientssummary.php?userid=<?= $transaction->user_id; ?>"><?= sprintf('%s %s', $transaction->firstname, $transaction->lastname); ?></a>
            </td>
            <td class="text-center">
                <a target="_blank" href="invoices.php?action=edit&id=<?= $transaction->invoice_id; ?>">
                    #<?= $transaction->invoice_id; ?>
                </a>
            </td>
            <td class="text-center"><?= formatCurrency($transaction->amount / ($vars['currencyType'] == 'IRT' ? 10 : 1)); ?></td>
            <td class="text-center">
                <span class="label <?= $statusBadgeColors[$transaction->status]; ?>">
                    <?= $statusTransaction[$transaction->status]; ?>
                </span>
            </td>
            <td class="text-center" dir="ltr"><?= $transaction->ip_address; ?></td>
            <td class="text-center"
                dir="ltr"><?= (empty($transaction->card_number) ? '-' : $transaction->card_number); ?></td>
            <td class="text-center" dir="ltr"><?= \ZarinpalAddon\jdate('Y-m-d H:i:s', $transaction->created_at); ?></td>
            <td class="text-center" dir="ltr"><?= \ZarinpalAddon\jdate('Y-m-d H:i:s', $transaction->updated_at); ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!sizeof($vars['transactions'])): ?>
        <tr>
            <td colspan="9" class="text-center">تراکنشی یافت نشد</td>
        </tr>
    <?php endif; ?>
</table>

<?php if ($vars['maxPage'] > 1): ?>
    <?php
    $query = array_merge([
        'module' => 'ZarinpalAddon',
        'tab'    => 'transactions',
    ], $vars['search']);
    ?>
    <br/>
    <ul class="pager">
        <li class="previous <?= ($vars['page'] <= 1 ? 'disabled' : null); ?>">
            <a href="addonmodules.php?<?= http_build_query(array_merge($query, ['page' => $vars['page'] - 1])); ?>">
                « « صفحه قبل
            </a>
        </li>
        <li class="next <?= ($vars['page'] >= $vars['maxPage'] ? 'disabled' : null); ?>">
            <a href="addonmodules.php?<?= http_build_query(array_merge($query, ['page' => $vars['page'] + 1])); ?>">
                صفحه بعد » »
            </a>
        </li>
    </ul>
<?php endif; ?>
