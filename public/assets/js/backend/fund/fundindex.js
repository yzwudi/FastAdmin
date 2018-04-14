define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'fund/fundindex/index',
                    add_url: 'fund/fundindex/add',
                    edit_url: 'fund/fundindex/edit',
                    del_url: 'fund/fundindex/del',
                    multi_url: 'fund/fundindex/multi',
                    table: 'fund_index_info',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'index_name', title: __('Index_name')},
                        {field: 'index', title: __('Index'), operate:'BETWEEN'},
                        {field: 'turn_volume', title: __('Turn_volume'), operate:'BETWEEN'},
                        {field: 'macd', title: __('Macd'), operate:'BETWEEN'},
                        {field: 'dif', title: __('Dif'), operate:'BETWEEN'},
                        {field: 'dea', title: __('Dea'), operate:'BETWEEN'},
                        {field: 'date', title: __('Date'), operate:'RANGE', addclass:'datetimerange'},
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