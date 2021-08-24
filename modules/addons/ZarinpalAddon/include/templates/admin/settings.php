<div class="row">
    <div class="col-12 col-lg-6 col-lg-offset-3">
        <h3>تنظیمات</h3>
        <?php if (isset($vars['success'])): ?>
            <div class="alert alert-success">
                <strong>تنظیمات با موفقیت ذخیره شد.</strong>
            </div>
        <?php endif; ?>
        <form action="addonmodules.php?module=ZarinpalAddon&tab=settings" method="post">
            <div class="form-group">
                <label class="col-form-label">فیلد شماره موبایل</label>
                <select class="form-control" name="mobile_customfield_id">
                    <option value="" <?= (empty($vars['settings']['mobile_customfield_id']) ? 'selected' : null); ?>></option>
                    <?php foreach ($vars['clientTextCustomfields'] as $id => $fieldname): ?>
                        <option value="<?= $id; ?>" <?= ($vars['settings']['mobile_customfield_id'] == $id ? 'selected' : null); ?>><?= $fieldname; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="col-form-label">اجباری بودن ای پی ایران</label>
                <select class="form-control" name="iran_access_only">
                    <option value="0" <?= ($vars['settings']['iran_access_only'] == 0 ? 'selected' : null); ?>>
                        غیرفعال
                    </option>
                    <option value="1" <?= ($vars['settings']['iran_access_only'] == 1 ? 'selected' : null); ?>>
                        فعال
                    </option>
                </select>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary">
                    ذخیره
                </button>
            </div>
        </form>
    </div>
</div>
