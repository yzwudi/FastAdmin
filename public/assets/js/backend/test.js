define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'test/index',
                    add_url: 'test/add',
                    edit_url: 'test/edit',
                    del_url: 'test/del',
                    multi_url: 'test/multi',
                    table: 'test',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'admin_id', title: __('Admin_id')},
                        {field: 'category_id', title: __('Category_id')},
                        {field: 'category_ids', title: __('Category_ids')},
                        {field: 'week', title: __('Week'), visible:false, searchList: {"monday":__('week monday'),"tuesday":__('week tuesday'),"wednesday":__('week wednesday')}},
                        {field: 'week_text', title: __('Week'), operate:false},
                        {field: 'flag', title: __('Flag'), searchList: {"hot":__('flag hot'),"index":__('flag index'),"recommend":__('flag recommend')}, formatter: Table.api.formatter.flag},
                        {field: 'genderdata', title: __('Genderdata'), visible:false, searchList: {"male":__('genderdata male'),"female":__('genderdata female')}},
                        {field: 'genderdata_text', title: __('Genderdata'), operate:false},
                        {field: 'hobbydata', title: __('Hobbydata'), visible:false, searchList: {"music":__('hobbydata music'),"reading":__('hobbydata reading'),"swimming":__('hobbydata swimming')}},
                        {field: 'hobbydata_text', title: __('Hobbydata'), operate:false, formatter: Table.api.formatter.label},
                        {field: 'title', title: __('Title')},
                        {field: 'image', title: __('Image'), formatter: Table.api.formatter.image},
                        {field: 'images', title: __('Images'), formatter: Table.api.formatter.images},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});