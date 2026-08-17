$(function() {
    $('.settings-tabs button').on('click', function() {
        var target = $(this).data('target');
        $('.settings-tabs button').removeClass('active');
        $(this).addClass('active');
        $('.settings-section').hide();
        $('#' + target).show();
    });
    // =========================================================
    // داشبورد - کالاهای کم‌موجودی
    // =========================================================

    function loadLowStockProducts(page) {

        if ($('body').attr('id') !== 'dashboard') {
            return;
        }

        page = parseInt(page, 10) || 1;

        var $tableBody = $('#lowStockTableBody');
        var $pagination = $('#lowStockPagination');

        if (!$tableBody.length) {
            return;
        }

        /*
         * هنگام دریافت اطلاعات، فقط خود جدول را در حالت loading قرار می‌دهیم.
         * کل صفحه refresh نمی‌شود.
         */
        $tableBody.html(
            '<tr>' +
                '<td colspan="2" class="empty-state">' +
                    'در حال دریافت اطلاعات...' +
                '</td>' +
            '</tr>'
        );

        $.ajax({
            url: 'assets/php/low_stock.php',
            type: 'GET',
            dataType: 'json',
            data: {
                page: page
            }
        })
        .done(function(response) {

            if (!response || !response.success) {

                $tableBody.html(
                    '<tr>' +
                        '<td colspan="2" class="empty-state">' +
                            'خطا در دریافت کالاهای کم‌موجودی' +
                        '</td>' +
                    '</tr>'
                );

                $pagination.empty();

                return;
            }

            var data = response.data || {};

            $tableBody.html(
                data.tableBody ||
                '<tr>' +
                    '<td colspan="2" class="empty-state">' +
                        'کالای کم‌موجودی وجود ندارد' +
                    '</td>' +
                '</tr>'
            );

            $pagination.html(data.pagination || '');
        })
        .fail(function() {

            $tableBody.html(
                '<tr>' +
                    '<td colspan="2" class="empty-state">' +
                        'خطا در ارتباط با سرور' +
                    '</td>' +
                '</tr>'
            );

            $pagination.empty();
        });
    }

    /*
     * کلیک روی شماره صفحات.
     *
     * چون این دکمه‌ها توسط AJAX ساخته می‌شوند،
     * از event delegation استفاده می‌کنیم.
     */
    $(document).on('click', '.dashboard-page-button', function() {

        if ($(this).prop('disabled')) {
            return;
        }

        var page = parseInt($(this).attr('data-page'), 10) || 1;

        loadLowStockProducts(page);
    });

    /*
     * فقط هنگام باز بودن داشبورد اطلاعات را دریافت می‌کنیم.
     */
    if ($('body').attr('id') === 'dashboard') {
        loadLowStockProducts(1);
    }
    //  اتمام کد کالای کم موجودی
    // ===== منوی کشویی «فاکتورها» =====
    $(document).on('click', '.nav-dropdown-toggle', function(e) {
        e.preventDefault();
        var $dd = $(this).closest('.nav-dropdown');
        var wasOpen = $dd.hasClass('open');
        $('.nav-dropdown.open').removeClass('open');
        if (!wasOpen) {
            $dd.addClass('open');
        }
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.nav-dropdown').length) {
            $('.nav-dropdown').removeClass('open');
        }
    });

    var selectedCategoryId = '';
    var selectedCategoryData = null;

    function escapeHtml(text) {
        return $('<div>').text(text || '').html();
    }

    function buildCustomerRow(customer) {
        var addressHtml = escapeHtml(customer.address).replace(/\n/g, '<br>');
        var row = '<tr class="customer-row" data-id="' + customer.id + '"'
            + ' data-first-name="' + escapeHtml(customer.first_name) + '"'
            + ' data-last-name="' + escapeHtml(customer.last_name) + '"'
            + ' data-national-code="' + escapeHtml(customer.national_code) + '"'
            + ' data-phone="' + escapeHtml(customer.phone) + '"'
            + ' data-economic-code="' + escapeHtml(customer.economic_code) + '"'
            + ' data-registration-number="' + escapeHtml(customer.registration_number) + '"'
            + ' data-address="' + escapeHtml(customer.address) + '"'
            + ' data-postal-code="' + escapeHtml(customer.postal_code) + '"'
            + ' data-note="' + escapeHtml(customer.note) + '">';
        row += '<td>' + escapeHtml(customer.full_name) + '</td>';
        row += '<td>' + escapeHtml(customer.national_code) + '</td>';
        row += '<td>' + escapeHtml(customer.phone) + '</td>';
        row += '<td>' + addressHtml + '</td>';
        row += '<td>' + escapeHtml(customer.total_spent) + '</td>';
        row += '<td>' + escapeHtml(customer.debt) + '</td>';
        row += '<td><button class="button-secondary small edit-customer" type="button">ویرایش</button> <button class="button-danger small delete-customer" type="button">حذف</button></td>';
        row += '</tr>';
        return row;
    }

    function openCustomerModal(mode, customerData) {
        $('#customerModalTitle').text(mode === 'edit' ? 'ویرایش مشتری' : 'افزودن مشتری جدید');
        $('#customerId').val(customerData && customerData.id ? customerData.id : '');
        $('#customerForm')[0].reset();

        if (mode === 'edit' && customerData) {
            $('#customerForm input[name="first_name"]').val(customerData.first_name);
            $('#customerForm input[name="last_name"]').val(customerData.last_name);
            $('#customerForm input[name="national_code"]').val(customerData.national_code);
            $('#customerForm input[name="phone"]').val(customerData.phone);
            $('#customerForm input[name="economic_code"]').val(customerData.economic_code);
            $('#customerForm input[name="registration_number"]').val(customerData.registration_number);
            $('#customerForm input[name="postal_code"]').val(customerData.postal_code);
            $('#customerForm textarea[name="address"]').val(customerData.address);
            $('#customerForm textarea[name="note"]').val(customerData.note);
        }

        $('#customerModal').addClass('open');
        $('body').addClass('modal-open');
    }

    $('#customerSearch').on('input', function() {
        var term = $(this).val().toLowerCase();
        var visible = 0;
        $('#customersTable tbody tr.customer-row').each(function() {
            var text = $(this).text().toLowerCase();
            var match = text.indexOf(term) !== -1;
            $(this).toggle(match);
            if (match) {
                visible++;
            }
        });
        $('.empty-state-row').toggle(visible === 0);
    });

    $('#openCustomerModal').on('click', function() {
        openCustomerModal('add');
    });

    $('#closeCustomerModal, #cancelCustomerModal, .modal-backdrop').on('click', function() {
        $('#customerModal').removeClass('open');
        $('body').removeClass('modal-open');
        $('#customerForm')[0].reset();
        $('#customerModalTitle').text('افزودن مشتری جدید');
        $('#customerId').val('');
    });

    $(document).on('click', '.edit-customer', function() {
        var $row = $(this).closest('tr.customer-row');
        openCustomerModal('edit', {
            id: $row.data('id'),
            first_name: $row.attr('data-first-name') || $row.data('firstName'),
            last_name: $row.attr('data-last-name') || $row.data('lastName'),
            national_code: $row.attr('data-national-code') || $row.data('nationalCode'),
            phone: $row.attr('data-phone') || $row.data('phone'),
            economic_code: $row.attr('data-economic-code') || $row.data('economicCode'),
            registration_number: $row.attr('data-registration-number') || $row.data('registrationNumber'),
            address: $row.attr('data-address') || $row.data('address'),
            postal_code: $row.attr('data-postal-code') || $row.data('postalCode'),
            note: $row.attr('data-note') || $row.data('note')
        });
    });

    $('#customerForm').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var data = $form.serialize();
        $.post($form.attr('action'), data, function(response) {
            if (response.success) {
                var customer = response.customer;
                var customerId = $('#customerId').val();
                if (customerId) {
                    var $row = $('#customersTable tbody tr.customer-row[data-id="' + customer.id + '"]');
                    var addressHtml = escapeHtml(customer.address).replace(/\n/g, '<br>');
                    $row.attr('data-first-name', customer.first_name);
                    $row.attr('data-last-name', customer.last_name);
                    $row.attr('data-national-code', customer.national_code);
                    $row.attr('data-phone', customer.phone);
                    $row.attr('data-economic-code', customer.economic_code);
                    $row.attr('data-registration-number', customer.registration_number);
                    $row.attr('data-address', customer.address);
                    $row.attr('data-postal-code', customer.postal_code);
                    $row.attr('data-note', customer.note);
                    $row.find('td').eq(0).text(customer.full_name);
                    $row.find('td').eq(1).text(customer.national_code);
                    $row.find('td').eq(2).text(customer.phone);
                    $row.find('td').eq(3).html(addressHtml);
                    // Force jQuery dataset refresh by re-adding the row HTML with updated attributes
                    $row.replaceWith(buildCustomerRow(customer));
                } else {
                    $('.empty-state-row').remove();
                    $('#customersTable tbody').prepend(buildCustomerRow(customer));
                }

                $('#customerModal').removeClass('open');
                $('body').removeClass('modal-open');
                $('#customerForm')[0].reset();
                $('#customerModalTitle').text('افزودن مشتری جدید');
                $('#customerId').val('');
            } else {
                alert(response.message || 'خطا در ذخیره‌سازی مشتری');
            }
        }, 'json').fail(function() {
            alert('خطا در ارتباط با سرور.');
        });
    });

    function buildSupplierRow(supplier) {
        var addressHtml = escapeHtml(supplier.address).replace(/\n/g, '<br>');
        var row = '<tr class="supplier-row" data-id="' + supplier.id + '"'
            + ' data-company-name="' + escapeHtml(supplier.company_name) + '"'
            + ' data-first-name="' + escapeHtml(supplier.first_name) + '"'
            + ' data-last-name="' + escapeHtml(supplier.last_name) + '"'
            + ' data-national-code="' + escapeHtml(supplier.national_code) + '"'
            + ' data-phone="' + escapeHtml(supplier.phone) + '"'
            + ' data-economic-code="' + escapeHtml(supplier.economic_code) + '"'
            + ' data-registration-number="' + escapeHtml(supplier.registration_number) + '"'
            + ' data-address="' + escapeHtml(supplier.address) + '"'
            + ' data-postal-code="' + escapeHtml(supplier.postal_code) + '"'
            + ' data-note="' + escapeHtml(supplier.note) + '">';
        row += '<td>' + escapeHtml(supplier.full_name) + '</td>';
        row += '<td>' + escapeHtml(supplier.national_code) + '</td>';
        row += '<td>' + escapeHtml(supplier.phone) + '</td>';
        row += '<td>' + addressHtml + '</td>';
        row += '<td>' + escapeHtml(supplier.total_purchases) + '</td>';
        row += '<td>' + escapeHtml(supplier.unpaid) + '</td>';
        row += '<td><button class="button-secondary small edit-supplier" type="button">ویرایش</button> <button class="button-danger small delete-supplier" type="button">حذف</button></td>';
        row += '</tr>';
        return row;
    }

    function openSupplierModal(mode, supplierData) {
        $('#supplierModalTitle').text(mode === 'edit' ? 'ویرایش تامین‌کننده' : 'افزودن تامین‌کننده جدید');
        $('#supplierId').val(supplierData && supplierData.id ? supplierData.id : '');
        $('#supplierForm')[0].reset();

        if (mode === 'edit' && supplierData) {
            $('#supplierForm input[name="company_name"]').val(supplierData.company_name);
            $('#supplierForm input[name="first_name"]').val(supplierData.first_name);
            $('#supplierForm input[name="last_name"]').val(supplierData.last_name);
            $('#supplierForm input[name="national_code"]').val(supplierData.national_code);
            $('#supplierForm input[name="phone"]').val(supplierData.phone);
            $('#supplierForm input[name="economic_code"]').val(supplierData.economic_code);
            $('#supplierForm input[name="registration_number"]').val(supplierData.registration_number);
            $('#supplierForm input[name="postal_code"]').val(supplierData.postal_code);
            $('#supplierForm textarea[name="address"]').val(supplierData.address);
            $('#supplierForm textarea[name="note"]').val(supplierData.note);
        }

        $('#supplierModal').addClass('open');
        $('body').addClass('modal-open');
    }

    function closeSupplierModal() {
        $('#supplierModal').removeClass('open');
        $('body').removeClass('modal-open');
        $('#supplierForm')[0].reset();
        $('#supplierModalTitle').text('افزودن تامین‌کننده جدید');
        $('#supplierId').val('');
    }


    $('#supplierSearch').on('input', function() {
        var term = $(this).val().toLowerCase();
        var visible = 0;
        $('#suppliersTable tbody tr.supplier-row').each(function() {
            var text = $(this).text().toLowerCase();
            var match = text.indexOf(term) !== -1;
            $(this).toggle(match);
            if (match) {
                visible++;
            }
        });
        $('#suppliersTable .empty-state-row').toggle(visible === 0);
    });

    $('#openSupplierModal').on('click', function() {
        openSupplierModal('add');
    });

    $('#closeSupplierModal, #cancelSupplierModal').on('click', closeSupplierModal);
    $('#supplierModal .modal-backdrop').on('click', closeSupplierModal);

    $(document).on('click', '.edit-supplier', function() {
        var $row = $(this).closest('tr.supplier-row');
        openSupplierModal('edit', {
            id: $row.data('id'),
            company_name: $row.attr('data-company-name') || $row.data('companyName'),
            first_name: $row.attr('data-first-name') || $row.data('firstName'),
            last_name: $row.attr('data-last-name') || $row.data('lastName'),
            national_code: $row.attr('data-national-code') || $row.data('nationalCode'),
            phone: $row.attr('data-phone') || $row.data('phone'),
            economic_code: $row.attr('data-economic-code') || $row.data('economicCode'),
            registration_number: $row.attr('data-registration-number') || $row.data('registrationNumber'),
            address: $row.attr('data-address') || $row.data('address'),
            postal_code: $row.attr('data-postal-code') || $row.data('postalCode'),
            note: $row.attr('data-note') || $row.data('note')
        });
    });

    $('#supplierForm').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var data = $form.serialize();
        $.post($form.attr('action'), data, function(response) {
            if (response.success) {
                var supplier = response.supplier;
                var supplierId = $('#supplierId').val();
                if (supplierId) {
                    var $row = $('#suppliersTable tbody tr.supplier-row[data-id="' + supplier.id + '"]');
                    var addressHtml = escapeHtml(supplier.address).replace(/\n/g, '<br>');
                    $row.attr('data-company-name', supplier.company_name);
                    $row.attr('data-first-name', supplier.first_name);
                    $row.attr('data-last-name', supplier.last_name);
                    $row.attr('data-national-code', supplier.national_code);
                    $row.attr('data-phone', supplier.phone);
                    $row.attr('data-economic-code', supplier.economic_code);
                    $row.attr('data-registration-number', supplier.registration_number);
                    $row.attr('data-address', supplier.address);
                    $row.attr('data-postal-code', supplier.postal_code);
                    $row.attr('data-note', supplier.note);
                    $row.find('td').eq(0).text(supplier.full_name);
                    $row.find('td').eq(1).text(supplier.national_code);
                    $row.find('td').eq(2).text(supplier.phone);
                    $row.find('td').eq(3).html(addressHtml);
                    $row.replaceWith(buildSupplierRow(supplier));
                } else {
                    $('#suppliersTable .empty-state-row').remove();
                    $('#suppliersTable tbody').prepend(buildSupplierRow(supplier));
                }

                closeSupplierModal();
            } else {
                alert(response.message || 'خطا در ذخیره‌سازی تامین‌کننده');
            }
        }, 'json').fail(function() {
            alert('خطا در ارتباط با سرور.');
        });
    });

    $(document).on('click', '.delete-customer', function() {
        var $row = $(this).closest('tr.customer-row');
        var id = $row.data('id');
        if (!confirm('آیا از حذف این مشتری اطمینان دارید؟')) {
            return;
        }
        $.post('assets/php/delete_customer.php', { customer_id: id }, function(response) {
            if (response.success) {
                $row.remove();
                if ($('#customersTable tbody tr.customer-row').length === 0) {
                    $('#customersTable tbody').append('<tr class="empty-state-row"><td colspan="7" class="empty-state">مشتری‌ای یافت نشد</td></tr>');
                }
            } else {
                alert(response.message || 'خطا در حذف مشتری.');
            }
        }, 'json').fail(function() {
            alert('خطا در ارتباط با سرور.');
        });
    });

    $(document).on('click', '.delete-supplier', function() {
        var $row = $(this).closest('tr.supplier-row');
        var id = $row.data('id');
        if (!confirm('آیا از حذف این تامین‌کننده اطمینان دارید؟')) {
            return;
        }
        $.post('assets/php/delete_supplier.php', { supplier_id: id }, function(response) {
            if (response.success) {
                $row.remove();
                if ($('#suppliersTable tbody tr.supplier-row').length === 0) {
                    $('#suppliersTable tbody').append('<tr class="empty-state-row"><td colspan="7" class="empty-state">تامین‌کننده‌ای یافت نشد</td></tr>');
                }
            } else {
                alert(response.message || 'خطا در حذف تامین‌کننده.');
            }
        }, 'json').fail(function() {
            alert('خطا در ارتباط با سرور.');
        });
    });

    function clearCategorySelection() {
        selectedCategoryId = '';
        selectedCategoryData = null;
        $('#modalCategoryTree .category-tree-item.selected').removeClass('selected');
    }

    function selectCategoryNode($node) {
        clearCategorySelection();
        $node.addClass('selected');
        selectedCategoryId = $node.data('id');
        selectedCategoryData = {
            id: selectedCategoryId,
            code: String($node.data('code') || ''),
            name: String($node.data('name') || ''),
            parent_id: String($node.data('parentId') || '')
        };
    }

    function refreshCategories(selectedNodeId, callback) {
        if ($('#categoriesTable').length === 0) {
            if (typeof callback === 'function') {
                callback();
            }
            return;
        }
        $.getJSON('assets/php/category_list.php').done(function(response) {
            if (!response.success) {
                alert('خطا در بارگذاری دسته‌بندی‌ها.');
                if (typeof callback === 'function') {
                    callback();
                }
                return;
            }
            $('#categoriesTable tbody').html(response.tableBody);
            $('#modalCategoryTree').html(response.modalTree);
            $('#categoryParent').html(response.parentOptions);
            clearCategorySelection();
            if (selectedNodeId) {
                var $node = $('#modalCategoryTree .category-tree-item[data-id="' + selectedNodeId + '"]');
                if ($node.length) {
                    selectCategoryNode($node);
                }
            }
            if (typeof callback === 'function') {
                callback();
            }
        }).fail(function() {
            alert('خطا در بارگذاری دسته‌بندی‌ها.');
            if (typeof callback === 'function') {
                callback();
            }
        });
    }

    function getCategoryCodes() {
        var codes = [];
        $('.category-tree-item').each(function() {
            var code = $(this).data('code');
            if (code) {
                codes.push(code.toString());
            }
        });
        return codes;
    }

    function generateRootCategoryCode() {
        var rootCodes = [];
        $('.category-tree-item').each(function() {
            var parentId = $(this).data('parentId');
            var code = $(this).data('code');
            if (parentId === '' || parentId === null) {
                rootCodes.push(parseInt(code, 10));
            }
        });
        var next = 1;
        if (rootCodes.length) {
            next = Math.max.apply(null, rootCodes) + 1;
        }
        return next.toString();
    }

    function generateSubcategoryCode(parentCode) {
        if (!parentCode) {
            return generateRootCategoryCode();
        }
        var siblingSuffixes = [];
        $('#modalCategoryTree .category-tree-item').each(function() {
            var code = $(this).data('code').toString();
            if (code.indexOf(parentCode) === 0 && code.length > parentCode.length) {
                var suffix = code.substr(parentCode.length);
                if (/^[0-9]+$/.test(suffix)) {
                    siblingSuffixes.push(parseInt(suffix, 10));
                }
            }
        });
        var next = 1;
        if (siblingSuffixes.length) {
            next = Math.max.apply(null, siblingSuffixes) + 1;
        }
        return parentCode + next.toString();
    }

    function findCategoryCodeById(categoryId) {
        var $item = $('#modalCategoryTree .category-tree-item[data-id="' + categoryId + '"]');
        return $item.length ? String($item.data('code')) : '';
    }

    function resetCategoryForm() {
        $('#categoryForm')[0].reset();
        $('#categoryMode').val('add-root');
        $('#categoryId').val('');
        $('#categoryCode').val('');
        $('#categoryCodeHidden').val('');
        $('#categoryName').prop('disabled', true);
        $('#categoryParent').prop('disabled', true).val('');
    }

    function openCategoryModal(mode) {
        resetCategoryForm();
        if (mode === 'add-root') {
            clearCategorySelection();
        }
        $('#categoryModal').addClass('open');
        $('body').addClass('modal-open');

        if (mode === 'add-root') {
            $('#categoryMode').val('add-root');
            $('#categoryName').prop('disabled', false).focus();
            $('#categoryParent').prop('disabled', true).val('');
            var code = generateRootCategoryCode();
            $('#categoryCode').val(code);
            $('#categoryCodeHidden').val(code);
        }
    }

    $('#categorySearch').on('input', function() {
        var term = $(this).val().toLowerCase();
        var visible = 0;
        $('#categoriesTable tbody tr.category-row').each(function() {
            var text = $(this).text().toLowerCase();
            var match = text.indexOf(term) !== -1;
            $(this).toggle(match);
            if (match) {
                visible++;
            }
        });
        if (visible === 0) {
            if ($('#categoriesTable tbody .empty-state').length === 0) {
                $('#categoriesTable tbody').append('<tr><td colspan="4" class="empty-state">دسته‌بندی‌ای یافت نشد</td></tr>');
            }
        } else {
            $('#categoriesTable tbody .empty-state').remove();
        }
    });

    $('#openCategoryModal').on('click', function() {
        openCategoryModal('add-root');
    });

    $('#addRootCategory').on('click', function() {
        openCategoryModal('add-root');
    });

    $('#addSubcategory').on('click', function() {
        if (!selectedCategoryId) {
            alert('ابتدا یک گروه اصلی را از درخت انتخاب کنید.');
            return;
        }
        refreshCategories(selectedCategoryId, function() {
            resetCategoryForm();
            $('#categoryMode').val('add-sub');
            $('#categoryName').prop('disabled', false).focus();
            $('#categoryParent').prop('disabled', false).val(selectedCategoryId);
            var code = generateSubcategoryCode(selectedCategoryData.code);
            $('#categoryCode').val(code);
            $('#categoryCodeHidden').val(code);
            $('#categoryModal').addClass('open');
            $('body').addClass('modal-open');
        });
    });

    $('#categoryParent').on('change', function() {
        var parentId = $(this).val();
        if (!parentId) {
            var code = generateRootCategoryCode();
            $('#categoryCode').val(code);
            $('#categoryCodeHidden').val(code);
            return;
        }
        var parentCode = findCategoryCodeById(parentId);
        if (!parentCode) {
            parentCode = $('#categoryCode').val();
        }
        var code = generateSubcategoryCode(parentCode);
        $('#categoryCode').val(code);
        $('#categoryCodeHidden').val(code);
    });

    $('#editCategory').on('click', function() {
        if (!selectedCategoryId) {
            alert('ابتدا یک گروه را برای ویرایش انتخاب کنید.');
            return;
        }
        resetCategoryForm();
        $('#categoryMode').val('edit');
        $('#categoryId').val(selectedCategoryId);
        $('#categoryCode').val(selectedCategoryData.code);
        $('#categoryCodeHidden').val(selectedCategoryData.code);
        $('#categoryName').prop('disabled', false).val(selectedCategoryData.name).focus();
        $('#categoryParent').prop('disabled', false).val(selectedCategoryData.parent_id || '');
        $('#categoryModal').addClass('open');
        $('body').addClass('modal-open');
    });

    $(document).on('click', '.category-row-edit', function(e) {
        e.stopPropagation();
        var $row = $(this).closest('tr.category-row');
        selectedCategoryId = $row.data('id');
        selectedCategoryData = {
            id: selectedCategoryId,
            code: $row.data('code'),
            name: $row.data('name'),
            parent_id: $row.data('parentId') || ''
        };
        resetCategoryForm();
        $('#categoryMode').val('edit');
        $('#categoryId').val(selectedCategoryId);
        $('#categoryCode').val(selectedCategoryData.code);
        $('#categoryCodeHidden').val(selectedCategoryData.code);
        $('#categoryName').prop('disabled', false).val(selectedCategoryData.name).focus();
        $('#categoryParent').prop('disabled', false).val(selectedCategoryData.parent_id || '');
        $('#categoryModal').addClass('open');
        $('body').addClass('modal-open');
    });

    $(document).on('click', '.category-row-delete', function(e) {
        e.stopPropagation();
        var $row = $(this).closest('tr.category-row');
        var categoryIdToDelete = $row.data('id');
        if (!confirm('آیا از حذف این دسته‌بندی مطمئن هستید؟')) {
            return;
        }
        $.post('assets/php/category_action.php', {mode: 'delete', category_id: categoryIdToDelete}, function(response) {
            if (response.success) {
                refreshCategories();
            } else {
                alert(response.message || 'خطا در حذف دسته‌بندی.');
            }
        }, 'json').fail(function() {
            alert('خطا در ارتباط با سرور.');
        });
    });

    $('#deleteCategory').on('click', function() {
        if (!selectedCategoryId) {
            alert('ابتدا یک گروه را برای حذف انتخاب کنید.');
            return;
        }
        if (!confirm('آیا از حذف این دسته‌بندی مطمئن هستید؟')) {
            return;
        }
        var formData = {
            mode: 'delete',
            category_id: selectedCategoryId
        };
        $.post('assets/php/category_action.php', formData, function(response) {
            if (response.success) {
                refreshCategories();
            } else {
                alert(response.message || 'خطا در حذف دسته‌بندی.');
            }
        }, 'json').fail(function() {
            alert('خطا در ارتباط با سرور.');
        });
    });

    $('#exitCategoryModal, #closeCategoryModal, #cancelCategoryModal').on('click', function() {
        $('#categoryModal').removeClass('open');
        $('body').removeClass('modal-open');
        resetCategoryForm();
        clearCategorySelection();
    });

    $(document).on('click', '.category-tree-item', function() {
        selectCategoryNode($(this));
    });

    $('#categoryForm').on('submit', function(e) {
        e.preventDefault();
        var formData = {
            mode: $('#categoryMode').val(),
            category_id: $('#categoryId').val(),
            code: $('#categoryCodeHidden').val(),
            name: $('#categoryName').val(),
            parent_id: $('#categoryParent').prop('disabled') ? '' : $('#categoryParent').val()
        };
        if (formData.mode !== 'delete' && formData.name.trim() === '') {
            alert('نام گروه نمی‌تواند خالی باشد.');
            return;
        }
        $.post('assets/php/category_action.php', formData, function(response) {
            if (response.success) {
                $('#categoryModal').removeClass('open');
                $('body').removeClass('modal-open');
                resetCategoryForm();
                refreshCategories(response.category ? response.category.parent_id || response.category.id : selectedCategoryId);
            } else {
                alert(response.message || 'خطا در ذخیره‌سازی دسته‌بندی.');
            }
        }, 'json').fail(function() {
            alert('خطا در ارتباط با سرور.');
        });
    });

    // ===== کالاها و خدمات =====
    var productsEditOriginal = null;

    function closeProductModal() {
        $('#productModal').removeClass('open');
        $('body').removeClass('modal-open');
        $('#productForm')[0].reset();
        $('#productId').val('');
        $('#productCodeHidden').val('');
        productsEditOriginal = null;
    }

    function productModalState(mode, data) {
        $('#productForm')[0].reset();
        $('#productId').val('');
        $('#productCodeHidden').val('');
        if (mode === 'add') {
            $('#productModalTitle').text('کالای جدید');
            $('#saveProductButton').text('ذخیره');
            $('#productCode').prop('disabled', false).val('');
            $.getJSON('assets/php/product_action.php', { action: 'next_code' }, function(resp) {
                if (resp && resp.success) {
                    $('#productCode').val(resp.code);
                    $('#productCodeHidden').val(resp.code);
                }
            });
        } else if (mode === 'edit' && data) {
            $('#productModalTitle').text('ویرایش کالا');
            $('#saveProductButton').text('تایید ویرایش');
            $('#productId').val(data.id);
            $('#productCode').prop('disabled', false).val(data.code);
            $('#productCodeHidden').val(data.code);
            $('#productName').val(data.name);
            $('#productCategory').val(data.category_id || '');
            $('#productType').val(data.type || 'product');
            $('#productUnit').val(data.unit || 'عدد');
            $('#productPurchasePrice').val(data.purchase_price);
            $('#productSalePrice').val(data.sale_price);
            $('#productStock').val(data.stock);
            $('#productMinStock').val(data.min_stock);
            $('#productDescription').val(data.description);
            productsEditOriginal = {
                name: data.name || '',
                category_id: data.category_id || '',
                type: data.type || 'product',
                unit: data.unit || 'عدد',
                purchase_price: (data.purchase_price === null || data.purchase_price === undefined) ? '' : String(data.purchase_price),
                sale_price: (data.sale_price === null || data.sale_price === undefined) ? '' : String(data.sale_price),
                stock: (data.stock === null || data.stock === undefined) ? '' : String(data.stock),
                min_stock: (data.min_stock === null || data.min_stock === undefined) ? '' : String(data.min_stock),
                description: data.description || ''
            };
        }
        $('#productModal').addClass('open');
        $('body').addClass('modal-open');
    }

    function refreshProducts() {
        if ($('#products-list').length === 0) {
            return;
        }
        var term = $('#productSearch').val() || '';
        var category = $('#productCategoryFilter').val() || '';
        $.getJSON('assets/php/product_list.php', { search: term, category: category }, function(response) {
            if (response && response.success) {
                $('#products-list tbody').html(response.tableBody);
                $('#productCount').text(response.totalProducts);
                $('#inventoryValue').text(response.inventoryValue);
            }
        });
    }

    $('#new-products').on('click', function(e) {
        e.preventDefault();
        productModalState('add');
    });

    $('#closeProductModal, #cancelProductModal, #productModal .modal-backdrop').on('click', function() {
        closeProductModal();
    });

    $(document).on('click', '.edit-product', function() {
        var $row = $(this).closest('tr.product-row');
        productModalState('edit', {
            id: $row.data('id'),
            code: $row.attr('data-code') || $row.data('code'),
            name: $row.attr('data-name') || $row.data('name'),
            category_id: $row.attr('data-category-id') || $row.data('categoryId'),
            type: $row.attr('data-type') || $row.data('type'),
            unit: $row.attr('data-unit') || $row.data('unit'),
            purchase_price: $row.attr('data-purchase-price') || $row.data('purchasePrice'),
            sale_price: $row.attr('data-sale-price') || $row.data('salePrice'),
            stock: $row.attr('data-stock') || $row.data('stock'),
            min_stock: $row.attr('data-min-stock') || $row.data('minStock'),
            description: $row.attr('data-description') || $row.data('description')
        });
    });

    $(document).on('click', '.delete-product', function() {
        var $row = $(this).closest('tr.product-row');
        var id = $row.data('id');
        var stock = parseFloat($row.attr('data-stock') || '0') || 0;
        var msg = 'آیا از حذف این محصول اطمینان دارید؟';
        if (stock > 0) {
            msg = 'کالا دارای موجودی است. آیا از حذف این محصول اطمینان دارید؟';
        }
        if (!confirm(msg)) {
            return;
        }
        $.post('assets/php/product_action.php', { action: 'delete', product_id: id }, function(response) {
            if (response.success) {
                refreshProducts();
            } else {
                alert(response.message || 'خطا در حذف کالا.');
            }
        }, 'json').fail(function() {
            alert('خطا در ارتباط با سرور.');
        });
    });

    $('#productForm').on('submit', function(e) {
        e.preventDefault();
        if ($('#productName').val().trim() === '') {
            alert('نام کالا نمی‌تواند خالی باشد.');
            return;
        }

        var productId = $('#productId').val();
        if (productId && productsEditOriginal) {
            var current = {
                name: $('#productName').val() || '',
                category_id: $('#productCategory').val() || '',
                type: $('#productType').val() || 'product',
                unit: $('#productUnit').val() || 'عدد',
                purchase_price: $('#productPurchasePrice').val() || '0',
                sale_price: $('#productSalePrice').val() || '0',
                stock: $('#productStock').val() || '0',
                min_stock: $('#productMinStock').val() || '0',
                description: $('#productDescription').val() || ''
            };
            var changed = false;
            for (var key in current) {
                if (String(current[key]) !== String(productsEditOriginal[key])) {
                    changed = true;
                    break;
                }
            }
            if (!changed) {
                closeProductModal();
                return;
            }
        }

        var $form = $(this);
        $.post($form.attr('action'), $form.serialize(), function(response) {
            if (response.success) {
                closeProductModal();
                refreshProducts();
            } else {
                alert(response.message || 'خطا در ذخیره‌سازی کالا.');
            }
        }, 'json').fail(function() {
            alert('خطا در ارتباط با سرور.');
        });
    });

    $('#productSearch').on('input', function() {
        refreshProducts();
    });

    $('#productCategoryFilter').on('change', function() {
        refreshProducts();
    });

    // ===== افزایش قیمت کالاها =====
    function refreshPriceRadioSelected() {
        $('.radio-group').each(function() {
            $(this).find('.radio-option').removeClass('selected');
            var $checked = $(this).find('input:checked').closest('.radio-option');
            if ($checked.length) {
                $checked.addClass('selected');
            }
        });
    }

    function openPriceIncreaseModal() {
        $('#priceIncreaseForm')[0].reset();
        $('#priceIncreaseValue').val('');
        $('#priceIncreaseResult').hide().removeClass('success error').empty();
        refreshPriceRadioSelected();
        $('#priceIncreaseModal').addClass('open');
        $('body').addClass('modal-open');
    }

    function closePriceIncreaseModal() {
        $('#priceIncreaseModal').removeClass('open');
        $('body').removeClass('modal-open');
        $('#applyPriceIncrease').prop('disabled', false).text('اعمال');
        $('#priceIncreaseResult').hide().removeClass('success error').empty();
    }

    $('#openPriceIncreaseModal').on('click', function() {
        openPriceIncreaseModal();
    });

    $('#closePriceIncreaseModal, #cancelPriceIncreaseModal, #priceIncreaseModal .modal-backdrop').on('click', function() {
        closePriceIncreaseModal();
    });

    $('body').on('change', '.radio-group input[type="radio"]', function() {
        refreshPriceRadioSelected();
    });

    $('#priceIncreaseForm').on('submit', function(e) {
        e.preventDefault();
        var scope = $('#priceScope').val() || 'all';
        var priceType = $('input[name="price_type"]:checked').val() || '';
        var increaseType = $('input[name="increase_type"]:checked').val() || '';
        var valueRaw = $('#priceIncreaseValue').val();

        if (!priceType || ['sale', 'purchase', 'both'].indexOf(priceType) === -1) {
            alert('نوع قیمت را انتخاب کنید.');
            return;
        }
        if (!increaseType || ['percent', 'amount'].indexOf(increaseType) === -1) {
            alert('نوع افزایش را انتخاب کنید.');
            return;
        }
        var value = parseFloat(valueRaw);
        if (valueRaw === '' || isNaN(value) || value <= 0) {
            alert('میزان افزایش باید یک عدد مثبت باشد.');
            return;
        }

        var priceText = { sale: 'قیمت فروش', purchase: 'قیمت خرید', both: 'قیمت فروش و خرید' }[priceType];
        var amountText = increaseType === 'percent' ? value + '٪' : value + ' واحد';
        var scopeText = ($('#priceScope option:selected').text() || 'همه کالاها').trim();

        var msg;
        if (scope === 'all') {
            msg = 'این عملیات قیمت تمام محصولات را تغییر خواهد داد. آیا مطمئن هستید؟';
        } else {
            msg = 'آیا از افزایش ' + amountText + ' ' + priceText + ' محصولات دسته‌بندی ' + scopeText + ' اطمینان دارید؟';
        }
        if (!confirm(msg)) {
            return;
        }

        var $btn = $('#applyPriceIncrease');
        $btn.prop('disabled', true).text('در حال اعمال...');

        $.post('assets/php/price_increase.php', {
            scope: scope,
            price_type: priceType,
            increase_type: increaseType,
            increase_value: valueRaw
        }, function(response) {
            $btn.prop('disabled', false).text('اعمال');
            var $result = $('#priceIncreaseResult');
            if (response && response.success) {
                var resAmount = (response.increase_type === 'percent')
                    ? response.increase_value + '٪'
                    : response.increase_value + ' واحد';
                var html = '<div>' + escapeHtml(response.message) + '</div>';
                html += '<ul class="price-result-list">';
                html += '<li>تعداد محصولات بررسی‌شده: ' + response.reviewed + '</li>';
                html += '<li>تعداد محصولات تغییرکرده: ' + response.changed + '</li>';
                html += '<li>نوع قیمت: ' + escapeHtml(response.price_label) + '</li>';
                html += '<li>نوع افزایش: ' + escapeHtml(response.increase_label) + '</li>';
                html += '<li>میزان افزایش: ' + escapeHtml(resAmount) + '</li>';
                html += '</ul>';
                $result.removeClass('error').addClass('success').html(html).show();
                refreshProducts();
            } else {
                $result.removeClass('success').addClass('error')
                    .text(response && response.message ? response.message : 'عملیات انجام نشد و هیچ تغییری در اطلاعات ایجاد نشده است.')
                    .show();
            }
        }, 'json').fail(function() {
            $btn.prop('disabled', false).text('اعمال');
            $('#priceIncreaseResult').removeClass('success').addClass('error')
                .text('خطا در ارتباط با سرور. عملیات انجام نشد و هیچ تغییری در اطلاعات ایجاد نشده است.')
                .show();
        });
    });

// ===== ورود کالاها و خدمات از اکسل =====
    var productImportFileVersion = 0;

    function resetProductImportUI() {
        $('#productImportResult').hide().removeClass('success error').empty();
        $('#productImportSummary').hide().empty();
        $('#productImportErrors').empty();
        $('#productImportErrorsWrap').hide();
        $('#productImportPreviewTable tbody').empty();
        $('#productImportPreviewWrap').hide();
        $('#productImportSpinner').hide();
        $('#productImportPreview').prop('disabled', true);
        $('#productImportSubmit').prop('disabled', true);
    }

    function openProductImportModal() {
        resetProductImportUI();
        $('#productImportModal').addClass('open');
        $('body').addClass('modal-open');
    }

    function closeProductImportModal() {
        $('#productImportModal').removeClass('open');
        $('body').removeClass('modal-open');
        $('#productImportFile').val('');
        productImportFileVersion++;
        resetProductImportUI();
    }

    $('#openProductImportModal').on('click', function(e) {
        e.preventDefault();
        openProductImportModal();
    });

    $('#closeProductImportModal, #cancelProductImportModal, #productImportModal .modal-backdrop').on('click', function() {
        closeProductImportModal();
    });

    $('#productImportFile').on('change', function() {
        productImportFileVersion++;
        resetProductImportUI();
        var file = this.files && this.files[0];
        if (file) {
            $('#productImportResult').addClass('success')
                .text('فایل «' + file.name + '» انتخاب شد. برای شروع، دکمه «خواندن و پیش‌نمایش» را بزنید.')
                .show();
        }
        $('#productImportPreview').prop('disabled', !file);
    });

    function buildProductImportRow(rec) {
        var d = rec.data || {};
        var typeLabel = d.type === 'service' ? 'خدمت' : 'محصول';
        var ok = rec.status === 'ok';
        var badgeHtml = ok
            ? '<span class="import-badge import-badge-ok">معتبر</span>'
            : '<span class="import-badge import-badge-error" title="' + escapeHtml((rec.errors || []).join(' | ')) + '">خطادار</span>';
        return '<tr class="' + (ok ? '' : 'import-row-error') + '">'
            + '<td>' + rec.row + '</td>'
            + '<td>' + escapeHtml(String(d.code || '')) + '</td>'
            + '<td>' + escapeHtml(String(d.name || '')) + '</td>'
            + '<td>' + escapeHtml(String(d.category_name || '-')) + '</td>'
            + '<td>' + escapeHtml(typeLabel) + '</td>'
            + '<td>' + escapeHtml(String(d.unit || '')) + '</td>'
            + '<td>' + escapeHtml(String(d.purchase_price || '')) + '</td>'
            + '<td>' + escapeHtml(String(d.sale_price || '')) + '</td>'
            + '<td>' + escapeHtml(String(d.stock || '')) + '</td>'
            + '<td>' + escapeHtml(String(d.min_stock || '')) + '</td>'
            + '<td>' + badgeHtml + '</td>'
            + '</tr>';
    }

$('#productImportPreview').on('click', function() {
        var inputEl = document.getElementById('productImportFile');
        if (!inputEl.files || !inputEl.files[0]) {
            alert('ابتدا یک فایل اکسل انتخاب کنید.');
            return;
        }
        var version = productImportFileVersion;
        var $btn = $(this);
        $btn.prop('disabled', true);
        $('#productImportSubmit').prop('disabled', true);
        $('#productImportResult').hide().removeClass('success error').empty();
        $('#productImportSpinner').show();

        var fd = new FormData();
        fd.append('csrf_token', $('#productCsrfToken').val() || '');
        fd.append('action', 'preview');
        fd.append('file', inputEl.files[0]);

        $.ajax({
            url: 'assets/php/product_import.php',
            type: 'POST',
            data: fd,
            contentType: false,
            processData: false,
            dataType: 'json'
        }).done(function(resp) {
            if (version !== productImportFileVersion) { return; }
            $('#productImportSpinner').hide();
            $btn.prop('disabled', false);
            if (resp && resp.success && resp.data) {
                var data = resp.data;
                $('#productImportSummary')
                    .html('<span class="import-summary-item import-summary-total">کل: ' + data.total + '</span>'
                        + '<span class="import-summary-item import-summary-ok">معتبر: ' + data.valid + '</span>'
                        + '<span class="import-summary-item import-summary-bad">خطادار: ' + data.invalid + '</span>')
                    .show();

                var errorsHtml = '';
                if (data.invalid > 0 && data.errors) {
                    data.errors.forEach(function(e) {
                        errorsHtml += '<li>' + escapeHtml(e) + '</li>';
                    });
                } else {
                    errorsHtml = '<li class="import-no-error">همه ردیف‌ها معتبر هستند.</li>';
                }
                if (data.truncated) {
                    errorsHtml += '<li class="import-no-error">نمایش محدود به ۵۰۰ ردیف اول است.</li>';
                }
                $('#productImportErrors').html(errorsHtml);
                $('#productImportErrorsWrap').show();

                var tbody = $('#productImportPreviewTable tbody').empty();
                (data.records || []).forEach(function(rec) {
                    tbody.append(buildProductImportRow(rec));
                });
                $('#productImportPreviewWrap').show();

                $('#productImportSubmit').prop('disabled', data.valid <= 0);
                if (data.valid === 0 && data.invalid > 0) {
                    $('#productImportResult').addClass('error')
                        .text('هیچ رکورد معتبری برای ثبت وجود ندارد. خطاها را بررسی کنید.')
                        .show();
                }
            } else {
                $('#productImportResult').addClass('error')
                    .text(resp && resp.message ? resp.message : 'خواندن فایل ناموفق بود.')
                    .show();
            }
        }).fail(function() {
            if (version !== productImportFileVersion) { return; }
            $('#productImportSpinner').hide();
            $btn.prop('disabled', false);
            $('#productImportResult').addClass('error').text('خطا در ارتباط با سرور.').show();
        });
    });
$('#productImportSubmit').on('click', function() {
        var inputEl = document.getElementById('productImportFile');
        if (!inputEl.files || !inputEl.files[0]) { return; }
        var version = productImportFileVersion;
        var $btn = $(this);
        $btn.prop('disabled', true);
        $('#productImportPreview').prop('disabled', true);
        $('#productImportSpinner').show();

        var fd = new FormData();
        fd.append('csrf_token', $('#productCsrfToken').val() || '');
        fd.append('action', 'import');
        fd.append('file', inputEl.files[0]);

        $.ajax({
            url: 'assets/php/product_import.php',
            type: 'POST',
            data: fd,
            contentType: false,
            processData: false,
            dataType: 'json'
        }).done(function(resp) {
            if (version !== productImportFileVersion) { return; }
            $('#productImportSpinner').hide();
            var $res = $('#productImportResult');
            if (resp && resp.success && resp.data) {
                var html = '<div>' + escapeHtml(resp.message) + '</div>';
                html += '<ul class="price-result-list">';
                html += '<li>تعداد کل ردیف‌ها: ' + resp.data.total + '</li>';
                html += '<li>تعداد ثبت‌شده: ' + resp.data.inserted + '</li>';
                html += '<li>تعداد ردشده: ' + resp.data.rejected + '</li>';
                html += '</ul>';
                $res.removeClass('error').addClass('success').html(html).show();
                $('#productImportSubmit').prop('disabled', true);
                refreshProducts();
            } else {
                $res.removeClass('success').addClass('error')
                    .text(resp && resp.message ? resp.message : 'ثبت نهایی انجام نشد؛ هیچ تغییری ثبت نشده است.')
                    .show();
                $btn.prop('disabled', false);
                $('#productImportPreview').prop('disabled', false);
            }
        }).fail(function() {
            if (version !== productImportFileVersion) { return; }
            $('#productImportSpinner').hide();
            $('#productImportResult').addClass('error')
                .text('خطا در ارتباط با سرور. فایل را دوباره پیش‌نمایش کنید.')
                .show();
            $btn.prop('disabled', false);
        });
    });
    $('.button.ajax-load').on('click', function(e) {
        e.preventDefault();
        var box = $(this).closest('.card').find('.empty-state');
        box.text('در حال بارگذاری اطلاعات...');
        $.ajax({
            url: 'assets/js/dummy.json',
            dataType: 'json'
        }).done(function() {
            box.text('داده‌ها با موفقیت بارگذاری شدند، اما هنوز جدول نمونه است.');
        }).fail(function() {
            box.text('خطا در بارگذاری اطلاعات.');
        });
    });

    var provinces = [];
    var cities = [];

    function normalizeSearchText(text) {
        return String(text || '').trim().toLowerCase();
    }

    function renderProvinceOptions(filter) {
        var searchText = normalizeSearchText(filter);
        var $select = $('#provinceSelect');
        var currentValue = $select.val();
        $select.empty();

        if (!provinces.length) {
            $select.append('<option value="">استانی یافت نشد</option>').prop('disabled', true);
            return;
        }

        $select.append('<option value="">انتخاب کنید</option>');
        provinces.filter(function(item) {
            return !searchText || String(item.name).toLowerCase().indexOf(searchText) !== -1;
        }).forEach(function(item) {
            $select.append('<option value="' + item.id + '">' + item.name + '</option>');
        });
        $select.prop('disabled', false);
        $select.val(currentValue);

        if (currentValue && !$select.find('option[value="' + currentValue + '"]').length) {
            $select.val('');
        }
    }

    function renderCityOptions(provinceId, filter) {
        var searchText = normalizeSearchText(filter);
        var $select = $('#citySelect');
        $select.empty();
        if (!provinceId) {
            $select.append('<option value="">ابتدا استان را انتخاب کنید</option>').prop('disabled', true);
            return;
        }

        var filteredCities = cities.filter(function(item) {
            return item.province_id === parseInt(provinceId, 10) && (!searchText || String(item.name).toLowerCase().indexOf(searchText) !== -1);
        });
        if (filteredCities.length === 0) {
            $select.append('<option value="">شهر یافت نشد</option>').prop('disabled', false);
            return;
        }
        $select.append('<option value="">انتخاب کنید</option>').prop('disabled', false);
        filteredCities.forEach(function(item) {
            $select.append('<option value="' + item.id + '">' + item.name + '</option>');
        });
    }

    function updateCityOptions() {
        var provinceId = $('#provinceSelect').val();
        var searchText = $('#citySearch').val().trim();
        renderCityOptions(provinceId, searchText);
    }

    function loadLocationData() {
        return $.when(
            $.getJSON('assets/data/provinces.json'),
            $.getJSON('assets/data/cities.json')
        ).done(function(provinceResponse, cityResponse) {
            provinces = Array.isArray(provinceResponse[0]) ? provinceResponse[0] : [];
            cities = Array.isArray(cityResponse[0]) ? cityResponse[0] : [];
            renderProvinceOptions('');
            renderCityOptions($('#provinceSelect').val(), '');
        }).fail(function() {
            console.error('خطا در بارگذاری داده‌های استان و شهر.');
            provinces = [];
            cities = [];
            renderProvinceOptions('');
            renderCityOptions('', '');
        });
    }

    function loadStoreSettings() {
        $.getJSON('assets/php/store_settings.php').done(function(response) {
            if (!response.success) {
                return;
            }
            var data = response.data || {};
            var settings = data.settings || {};
            $('#storeName').val(settings.store_name || '');
            $('#economicCode').val(settings.economic_code || '');
            $('#nationalCode').val(settings.national_code || '');
            $('#registrationNumber').val(settings.registration_number || '');
            $('#postalCode').val(settings.postal_code || '');
            $('#phone').val(settings.phone || '');
            $('#address').val(settings.address || '');
            $('#defaultSize').val(settings.default_size_percentage || '80');
                if (settings.province_id) {
                $('#provinceSelect').val(settings.province_id);
            }
            renderProvinceOptions('');
            $('#citySearch').prop('disabled', !settings.province_id);
            $('#citySelect').prop('disabled', !settings.province_id);
            renderCityOptions($('#provinceSelect').val(), '');
            if (settings.city_id) {
                $('#citySelect').val(settings.city_id);
            }
            if (settings.logo_path) {
                $('#logoPreview').attr('src', settings.logo_path).removeAttr('hidden');
            }
            if (settings.signature_path) {
                $('#signaturePreview').attr('src', settings.signature_path).removeAttr('hidden');
            }
            if (settings.stamp_path) {
                $('#stampPreview').attr('src', settings.stamp_path).removeAttr('hidden');
            }
        }).fail(function() {
            console.error('خطا در دریافت تنظیمات فروشگاه.');
        });
    }

    $('#provinceSearch').on('input', function() {
        var text = $(this).val().trim();
        renderProvinceOptions(text);
    });

    $('#citySearch').on('input', function() {
        updateCityOptions();
    });

    $('#provinceSelect').on('change', function() {
        $('#citySearch').val('');
        $('#citySearch').prop('disabled', false);
        $('#citySelect').prop('disabled', false);
        updateCityOptions();
    });

    $('.image-upload-field input[type="file"]').on('change', function() {
        var input = this;
        var file = input.files && input.files[0];
        if (!file) {
            return;
        }
        var reader = new FileReader();
        reader.onload = function(e) {
            var previewId = '#' + $(input).attr('id').replace('Image', 'Preview');
            $(previewId).attr('src', e.target.result).removeAttr('hidden');
        };
        reader.readAsDataURL(file);
    });

    $('.image-upload-field').on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            var inputId = $(this).data('input');
            $('#' + inputId).trigger('click');
        }
    });

    $('.image-upload-field').on('click', function(e) {
        if (e.target.tagName.toLowerCase() === 'input') {
            return;
        }
        var inputId = $(this).data('input');
        $('#' + inputId).trigger('click');
    });

    $('#storeSettingsForm').on('submit', function(e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        var messageEl = $('.settings-save-message');
        messageEl.text('در حال ذخیره‌سازی...');
        $.ajax({
            url: 'assets/php/store_settings.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json'
        }).done(function(response) {
            if (response.success) {
                messageEl.css('color', 'var(--accent)').text(response.message || 'تنظیمات ذخیره شد.');
                loadStoreSettings();
            } else {
                messageEl.css('color', 'var(--danger)').text(response.message || 'خطا در ذخیره‌سازی تنظیمات.');
            }
        }).fail(function() {
            messageEl.css('color', 'var(--danger)').text('خطا در ارتباط با سرور.');
        });
    });

    function loadInvoiceSettings() {
        $.getJSON('assets/php/invoice_settings.php').done(function(response) {
            if (!response.success) {
                return;
            }
            var data = response.data || {};
            var settings = data.settings || {};
            $('#unofficialInvoiceDesc').val(settings.unofficial_invoice_desc || '');
            $('#officialInvoiceDesc').val(settings.official_invoice_desc || '');
            $('#proformaDesc').val(settings.proforma_desc || '');
            $('#proformaTitle').val(settings.proforma_title || '');
            if (settings.invoice_template_color) {
                $('#invoiceTemplateColor').val(settings.invoice_template_color);
            }
            if (settings.official_invoice_direction) {
                $('#officialDirection').val(settings.official_invoice_direction);
            }
            if (settings.unofficial_invoice_direction) {
                $('#unofficialDirection').val(settings.unofficial_invoice_direction);
            }
        }).fail(function() {
            console.error('خطا در دریافت تنظیمات فاکتور.');
        });
    }

    $('#invoiceSettingsForm').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var messageEl = $('.invoice-settings-save-message');
        messageEl.text('در حال ذخیره‌سازی...');
        $.post($form.attr('action') || 'assets/php/invoice_settings.php', $form.serialize(), function(response) {
            if (response.success) {
                messageEl.css('color', 'var(--accent)').text(response.message || 'تنظیمات فاکتور ذخیره شد.');
                loadInvoiceSettings();
            } else {
                messageEl.css('color', 'var(--danger)').text(response.message || 'خطا در ذخیره‌سازی تنظیمات فاکتور.');
            }
        }, 'json').fail(function() {
            messageEl.css('color', 'var(--danger)').text('خطا در ارتباط با سرور.');
        });
    });

    loadInvoiceSettings();

    /* ===== تنظیمات مالیات (تب ۳) ===== */
    function updateTaxStatusText() {
        var on = $('#taxEnabled').is(':checked');
        $('#taxStatusText').text(on ? 'مالیات فعال است' : 'مالیات غیرفعال است');
    }

    function loadTaxSettings() {
        $.getJSON('assets/php/tax_settings.php').done(function(response) {
            if (!response.success) {
                return;
            }
            var s = response.data.settings || {};
            $('#taxEnabled').prop('checked', s.tax_enabled == 1);
            $('#taxRate').val(s.tax_rate != null ? s.tax_rate : 0);
            updateTaxStatusText();
        }).fail(function() {
            console.error('خطا در دریافت تنظیمات مالیات.');
        });
    }

    $('#taxEnabled').on('change', updateTaxStatusText);

    $('#taxSettingsForm').on('submit', function(e) {
        e.preventDefault();
        var msg = $('.tax-settings-save-message');
        msg.text('در حال ذخیره...');
        $.post('assets/php/tax_settings.php', $(this).serialize(), function(response) {
            if (response.success) {
                msg.css('color', 'var(--accent)').text(response.message || 'تنظیمات مالیات ذخیره شد.');
                loadTaxSettings();
            } else {
                msg.css('color', 'var(--danger)').text(response.message || 'خطا در ذخیره تنظیمات مالیات.');
            }
        }, 'json').fail(function() {
            msg.css('color', 'var(--danger)').text('خطا در ارتباط با سرور.');
        });
    });

    /* ===== پایگاه داده (تب ۴) ===== */
    function loadDbBackup() {
        $.getJSON('assets/php/db_backup.php').done(function(response) {
            if (!response.success) {
                return;
            }
            var data = response.data || {};
            var saved = data.backup_dir || '';
            // اگر آدرس ذخیره‌ای نبود، مقدار پیش‌فرض را نگه دار
            $('#backupDirInput').val(saved || 'C:\\backups');
        }).fail(function() {
            console.error('خطا در دریافت آدرس پشتیبان.');
        });
    }

    function showBackupMsg(msg, text, color) {
        msg.css('color', color || 'var(--text)').text(text || '');
        clearTimeout(msg.data('timer'));
        if (text) {
            msg.data('timer', setTimeout(function() {
                msg.text('');
            }, 6000));
        }
    }

    $('#saveBackupDir').on('click', function() {
        var msg = $('.backup-message');
        var dir = $('#backupDirInput').val().trim();
        if (!dir) {
            showBackupMsg(msg, 'آدرس پوشه پشتیبان نمی‌تواند خالی باشد.', 'var(--danger)');
            return;
        }
        showBackupMsg(msg, 'در حال ذخیره...');
        $.post('assets/php/db_backup.php', { action: 'save_dir', backup_dir: dir }, function(response) {
            if (response.success) {
                showBackupMsg(msg, response.message || 'آدرس پوشه پشتیبان ذخیره شد.', 'var(--accent)');
                loadDbBackup();
            } else {
                showBackupMsg(msg, response.message || 'خطا در ذخیره آدرس پشتیبان.', 'var(--danger)');
            }
        }, 'json').fail(function() {
            showBackupMsg(msg, 'خطا در ارتباط با سرور.', 'var(--danger)');
        });
    });

    $('#createBackup').on('click', function() {
        var msg = $('.backup-message');
        if (!$('#backupDirInput').val().trim()) {
            showBackupMsg(msg, 'ابتدا آدرس پوشه پشتیبان را وارد و ذخیره کنید.', 'var(--danger)');
            loadDbBackup();
            return;
        }
        showBackupMsg(msg, 'در حال تهیه نسخه پشتیبان...');
        $.post('assets/php/db_backup.php', { action: 'backup' }, function(response) {
            if (response.success) {
                var filename = (response.data && response.data.filename) ? response.data.filename : '';
                var location = (response.data && response.data.path) ? ' (' + response.data.path + ')' : '';
                showBackupMsg(msg, 'نسخه پشتیبان با موفقیت تهیه شد: ' + filename + location, 'var(--accent)');
            } else {
                showBackupMsg(msg, response.message || 'خطا در تهیه نسخه پشتیبان.', 'var(--danger)');
            }
        }, 'json').fail(function() {
            showBackupMsg(msg, 'خطا در ارتباط با سرور.', 'var(--danger)');
        });
    });

    $('#createFilesBackup').on('click', function() {
        var msg = $('.backup-message');
        if (!$('#backupDirInput').val().trim()) {
            showBackupMsg(msg, 'ابتدا آدرس پوشه پشتیبان را وارد و ذخیره کنید.', 'var(--danger)');
            loadDbBackup();
            return;
        }
        showBackupMsg(msg, 'در حال تهیه نسخه پشتیبان از تمام فایل‌ها...');
        $.post('assets/php/db_backup.php', { action: 'backup_files' }, function(response) {
            if (response.success) {
                var location = (response.data && response.data.path) ? ' (' + response.data.path + ')' : '';
                showBackupMsg(msg, 'پشتیبان‌گیری از تمام فایل‌ها با موفقیت انجام شد' + location, 'var(--accent)');
            } else {
                showBackupMsg(msg, response.message || 'خطا در تهیه نسخه پشتیبان از فایل‌ها.', 'var(--danger)');
            }
        }, 'json').fail(function() {
            showBackupMsg(msg, 'خطا در ارتباط با سرور.', 'var(--danger)');
        });
    });

    $('#restoreFilePick').on('click', function() {
        $('#restoreFileInput').trigger('click');
    });

    $('#restoreFileInput').on('change', function() {
        var file = this.files && this.files[0];
        if (file) {
            $('#restoreFileName').text(file.name);
        }
    });

    $('#restoreBackup').on('click', function() {
        var msg = $('.restore-message');
        var fileInput = document.getElementById('restoreFileInput');
        if (!fileInput.files || fileInput.files.length === 0) {
            msg.css('color', 'var(--danger)').text('ابتدا یک فایل SQL انتخاب کنید.');
            return;
        }
        var confirmed = confirm('هشدار: با بازیابی این نسخه پشتیبان، تمام اطلاعات فعلی پایگاه داده (مشتریان، تامین‌کنندگان، کالاها و ...) حذف و با داده‌های این فایل جایگزین می‌شود.\nآیا مطمئن هستید که عملیات بازیابی شروع شود؟');
        if (!confirmed) {
            return;
        }
        msg.text('در حال بازیابی...');
        var fd = new FormData();
        fd.append('action', 'restore');
        fd.append('backup_file', fileInput.files[0]);
        $.ajax({
            url: 'assets/php/db_backup.php',
            type: 'POST',
            data: fd,
            contentType: false,
            processData: false,
            dataType: 'json'
        }).done(function(response) {
            if (response.success) {
                msg.css('color', 'var(--accent)').text(response.message || 'بازیابی نسخه پشتیبان با موفقیت انجام شد.');
                $('#restoreFileInput').val('');
                $('#restoreFileName').text('انتخاب فایل SQL...');
                loadDbBackup();
                loadTaxSettings();
            } else {
                msg.css('color', 'var(--danger)').text(response.message || 'خطا در بازیابی نسخه پشتیبان.');
            }
        }).fail(function() {
            msg.css('color', 'var(--danger)').text('خطا در ارتباط با سرور.');
        });
    });

    loadTaxSettings();
    loadDbBackup();

    loadLocationData().done(loadStoreSettings);

    // =========================================================
    // گزارشات — فیلتر/جستجو و انتقال فیلترها به خروجی اکسل
    // =========================================================

    function updateEmptyState($tbody, visible, colspan) {
        var $empty = $tbody.find('.empty-state-row');
        if (visible === 0) {
            if ($empty.length === 0) {
                $tbody.append('<tr class="empty-state-row"><td colspan="' + colspan + '" class="empty-state">نتیجه‌ای یافت نشد</td></tr>');
            } else {
                $empty.show();
            }
        } else {
            $empty.remove();
        }
    }

    // ---- گزارش موجودی کالاها ----
    if ($('body').attr('id') === 'report-inventory') {
        function refreshInventoryReport() {
            var term = $('#inventorySearch').val() || '';
            var category = $('#inventoryCategoryFilter').val() || '';
            var type = $('#inventoryTypeFilter').val() || '';
            var visible = 0;
            $('#inventoryReportTable tbody tr.inventory-report-row').each(function() {
                var $r = $(this);
                var text = ($r.data('name') + ' ' + $r.data('category') + ' ' + $r.data('type')).toLowerCase();
                var match = text.indexOf(term.toLowerCase()) !== -1;
                if (category !== '' && String($r.data('categoryId') || '') !== category) {
                    match = false;
                }
                if (type !== '' && $r.data('type') !== type) {
                    match = false;
                }
                $r.toggle(match);
                if (match) { visible++; }
            });
            updateEmptyState($('#inventoryReportTable tbody'), visible, 6);
        }

        $('#inventorySearch').on('input', refreshInventoryReport);
        $('#inventoryCategoryFilter').on('change', refreshInventoryReport);
        $('#inventoryTypeFilter').on('change', refreshInventoryReport);

        $('#exportInventoryBtn').on('click', function(e) {
            var url = 'assets/php/report_inventory_export.php'
                + '?search=' + encodeURIComponent($('#inventorySearch').val() || '')
                + '&category=' + encodeURIComponent($('#inventoryCategoryFilter').val() || '')
                + '&type=' + encodeURIComponent($('#inventoryTypeFilter').val() || '');
            $(this).attr('href', url);
        });
    }

    // ---- گزارش بدهی مشتریان ----
    if ($('body').attr('id') === 'report-customer-debt') {
        function refreshCustomerDebtReport() {
            var term = $('#customerDebtSearch').val() || '';
            var visible = 0;
            $('#customerDebtReportTable tbody tr.customer-debt-row').each(function() {
                var m = ($(this).data('name') || '').toLowerCase().indexOf(term.toLowerCase()) !== -1;
                $(this).toggle(m);
                if (m) { visible++; }
            });
            updateEmptyState($('#customerDebtReportTable tbody'), visible, 5);
        }
        $('#customerDebtSearch').on('input', refreshCustomerDebtReport);
        $('#exportCustomerDebtBtn').on('click', function(e) {
            var url = 'assets/php/report_customer_debt_export.php'
                + '?search=' + encodeURIComponent($('#customerDebtSearch').val() || '');
            $(this).attr('href', url);
        });
    }

    // ---- گزارش بدهی به تامین‌کنندگان ----
    if ($('body').attr('id') === 'report-supplier-debt') {
        function refreshSupplierDebtReport() {
            var term = $('#supplierDebtSearch').val() || '';
            var visible = 0;
            $('#supplierDebtReportTable tbody tr.supplier-debt-row').each(function() {
                var m = ($(this).data('name') || '').toLowerCase().indexOf(term.toLowerCase()) !== -1;
                $(this).toggle(m);
                if (m) { visible++; }
            });
            updateEmptyState($('#supplierDebtReportTable tbody'), visible, 5);
        }
        $('#supplierDebtSearch').on('input', refreshSupplierDebtReport);
        $('#exportSupplierDebtBtn').on('click', function(e) {
            var url = 'assets/php/report_supplier_debt_export.php'
                + '?search=' + encodeURIComponent($('#supplierDebtSearch').val() || '');
            $(this).attr('href', url);
        });
    }
});
/* ============================================================
 * Sticky Notes
 * ========================================================== */

$(function () {

    if (document.body.id !== 'notes') {
        return;
    }

    var $board = $('#notesBoard');

    if (!$board.length) {
        return;
    }

    var csrfToken = $board.attr('data-csrf-token') || '';

    var noteColors = [
        '#fff3a3',
        '#ffd6a5',
        '#ffadad',
        '#caffbf',
        '#bde0fe',
        '#d9c2ff'
    ];

    var zIndexCounter = 100;


    function escapeHtml(text) {
        return $('<div>').text(text || '').html();
    }


    function showNoteStatus($note, text, isError) {

        var $status = $note.find('.sticky-note-status');

        $status
            .removeClass('success error')
            .addClass(isError ? 'error' : 'success')
            .text(text || '');

        clearTimeout($status.data('timer'));

        if (text) {
            var timer = setTimeout(function () {
                $status.text('').removeClass('success error');
            }, 3000);

            $status.data('timer', timer);
        }
    }


    function updateEmptyState() {

        var hasNotes = $board.find('.sticky-note').length > 0;

        if (!hasNotes) {

            if (!$('#notesEmptyState').length) {

                $board.append(
                    '<div id="notesEmptyState" class="notes-empty-state">' +
                        '<div class="notes-empty-icon">' +
                            '<i class="fas fa-sticky-note"></i>' +
                        '</div>' +
                        '<h3>هنوز یادداشتی ایجاد نشده است</h3>' +
                        '<p>برای ایجاد اولین یادداشت روی «یادداشت جدید» کلیک کنید.</p>' +
                    '</div>'
                );
            }

        } else {
            $('#notesEmptyState').remove();
        }
    }


    function buildNote(note) {

        var color = note.color || '#fff3a3';

        var $note = $('<article>', {
            'class': 'sticky-note',
            'data-id': note.id,
            'data-color': color
        });

        $note.css({
            '--note-color': color,
            left: parseInt(note.pos_x, 10) || 30,
            top: parseInt(note.pos_y, 10) || 30,
            zIndex: parseInt(note.z_index, 10) || ++zIndexCounter
        });

        $note.html(
            '<div class="sticky-note-header drag-handle">' +

                '<span class="sticky-note-grip">' +
                    '<i class="fas fa-grip-horizontal"></i>' +
                '</span>' +

                '<button type="button" class="sticky-note-delete" title="حذف یادداشت">' +
                    '<i class="fas fa-trash"></i>' +
                '</button>' +

            '</div>' +

            '<div class="sticky-note-body">' +

                '<input type="text" ' +
                    'class="sticky-note-title" ' +
                    'maxlength="150" ' +
                    'placeholder="عنوان یادداشت" ' +
                    'value="' + escapeHtml(note.title) + '">' +

                '<textarea ' +
                    'class="sticky-note-content" ' +
                    'maxlength="5000" ' +
                    'placeholder="متن یادداشت...">' +
                    escapeHtml(note.content) +
                '</textarea>' +

            '</div>' +

            '<div class="sticky-note-footer">' +

                '<span class="sticky-note-status"></span>' +

                '<button type="button" class="sticky-note-save">' +
                    'ذخیره' +
                '</button>' +

            '</div>'
        );

        return $note;
    }


    function saveNote($note, callback) {

        var id = parseInt($note.attr('data-id'), 10);

        if (!id) {
            return;
        }

        var title = $note.find('.sticky-note-title').val().trim();
        var content = $note.find('.sticky-note-content').val();

        var posX = Math.round(parseFloat($note.css('left')) || 0);
        var posY = Math.round(parseFloat($note.css('top')) || 0);
        var zIndex = parseInt($note.css('z-index'), 10) || 1;

        var color = $note.attr('data-color') || '#fff3a3';

        $.ajax({
            url: 'assets/php/note_action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                csrf_token: csrfToken,
                action: 'update',
                id: id,
                title: title,
                content: content,
                color: color,
                pos_x: posX,
                pos_y: posY,
                z_index: zIndex
            }
        })
        .done(function (response) {

            if (response.success) {
                showNoteStatus($note, 'ذخیره شد', false);

                if (typeof callback === 'function') {
                    callback(true, response);
                }

            } else {

                showNoteStatus(
                    $note,
                    response.message || 'خطا در ذخیره یادداشت',
                    true
                );

                if (typeof callback === 'function') {
                    callback(false, response);
                }
            }

        })
        .fail(function () {

            showNoteStatus(
                $note,
                'خطا در ارتباط با سرور',
                true
            );

            if (typeof callback === 'function') {
                callback(false);
            }
        });
    }


    /*
     * ایجاد یادداشت جدید
     */
    $('#createNoteButton').on('click', function () {

        var $button = $(this);

        if ($button.prop('disabled')) {
            return;
        }

        $button.prop('disabled', true);

        var color =
            noteColors[
                Math.floor(Math.random() * noteColors.length)
            ];

        $.ajax({
            url: 'assets/php/note_action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                csrf_token: csrfToken,
                action: 'create',
                title: 'یادداشت جدید',
                content: '',
                color: color
            }
        })
        .done(function (response) {

            if (!response.success || !response.data || !response.data.note) {

                alert(
                    response.message ||
                    'خطا در ایجاد یادداشت.'
                );

                return;
            }

            var note = response.data.note;

            var $note = buildNote(note);

            $board.append($note);

            updateEmptyState();

            zIndexCounter++;

            $note.css('z-index', zIndexCounter);

            $note.find('.sticky-note-title').focus().select();

        })
        .fail(function () {

            alert('خطا در ارتباط با سرور.');

        })
        .always(function () {

            $button.prop('disabled', false);

        });

    });


    /*
     * ذخیره دستی
     */
    $(document).on('click', '.sticky-note-save', function () {

        var $note = $(this).closest('.sticky-note');

        saveNote($note);

    });


    /*
     * Ctrl + Enter داخل متن = ذخیره
     */
    $(document).on(
        'keydown',
        '.sticky-note-content',
        function (e) {

            if (e.ctrlKey && e.key === 'Enter') {

                e.preventDefault();

                saveNote(
                    $(this).closest('.sticky-note')
                );
            }
        }
    );


    /*
     * ذخیره هنگام خروج از عنوان یا متن
     */
    $(document).on(
        'blur',
        '.sticky-note-title, .sticky-note-content',
        function () {

            var $note = $(this).closest('.sticky-note');

            saveNote($note);

        }
    );


    /*
     * حذف
     */
    $(document).on('click', '.sticky-note-delete', function () {

        var $note = $(this).closest('.sticky-note');
        var id = parseInt($note.attr('data-id'), 10);

        if (!id) {
            return;
        }

        if (!window.confirm('آیا از حذف این یادداشت مطمئن هستید؟')) {
            return;
        }

        var $deleteButton = $(this);

        $deleteButton.prop('disabled', true);

        $.ajax({
            url: 'assets/php/note_action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                csrf_token: csrfToken,
                action: 'delete',
                id: id
            }
        })
        .done(function (response) {

            if (!response.success) {

                alert(
                    response.message ||
                    'خطا در حذف یادداشت.'
                );

                $deleteButton.prop('disabled', false);

                return;
            }

            $note.fadeOut(180, function () {

                $(this).remove();

                updateEmptyState();

            });

        })
        .fail(function () {

            alert('خطا در ارتباط با سرور.');

            $deleteButton.prop('disabled', false);

        });

    });


    /*
     * جابه‌جایی Sticky Note
     */
    $(document).on(
        'mousedown',
        '.sticky-note .drag-handle',
        function (e) {

            if ($(e.target).closest('button').length) {
                return;
            }

            var $note = $(this).closest('.sticky-note');

            var boardOffset = $board.offset();

            var startMouseX = e.pageX;
            var startMouseY = e.pageY;

            var startLeft =
                parseInt($note.css('left'), 10) || 0;

            var startTop =
                parseInt($note.css('top'), 10) || 0;

            zIndexCounter++;

            $note.css('z-index', zIndexCounter);

            $note.addClass('dragging');

            function moveNote(ev) {

                var newLeft =
                    startLeft +
                    (ev.pageX - startMouseX);

                var newTop =
                    startTop +
                    (ev.pageY - startMouseY);

                newLeft = Math.max(0, newLeft);
                newTop = Math.max(0, newTop);

                $note.css({
                    left: newLeft,
                    top: newTop
                });
            }

            function stopMove() {

                $(document).off('mousemove.stickyNote');
                $(document).off('mouseup.stickyNote');

                $note.removeClass('dragging');

                /*
                 * موقعیت جدید هم AJAX ذخیره می‌شود.
                 */
                saveNote($note);
            }

            $(document).on(
                'mousemove.stickyNote',
                moveNote
            );

            $(document).on(
                'mouseup.stickyNote',
                stopMove
            );

            e.preventDefault();
        }
    );


    /*
     * با کلیک روی هر Note آن را به بالاترین لایه می‌آوریم.
     */
    $(document).on(
        'mousedown',
        '.sticky-note',
        function () {

            var $note = $(this);

            zIndexCounter++;

            $note.css('z-index', zIndexCounter);

        }
    );


    updateEmptyState();

});