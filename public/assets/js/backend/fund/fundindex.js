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
                        // {field: 'id', title: __('Id')},
                        {field: 'index_name', title: __('Index_name'), searchList: {
                            "沪深300":__('沪深300'),
                            "创业板指":__('创业板指'),
                            "上证指数":__('上证指数'),
                            "深证成指":__('深证成指'),
                            "中小板指":__('中小板指'),
                            "上证50":__('上证50'),
                            "B股指数":__('B股指数'),
                            "A股指数":__('A股指数'),
                        }},
                        {field: 'status', title: __('Status'), visible:false, searchList: {
                                "1":__('有效'),
                                "0":__('无效'),
                            }},
                        {field: 'date', title: __('Date'), operate:'RANGE', addclass:'datetimerange', sortable: true},
                        {field: 'index', title: __('Index'), operate:'BETWEEN', sortable: true},
                        {field: 'turn_volume', title: __('Turn_volume'), operate:'BETWEEN', sortable: true},
                        {field: 'macd', title: __('Macd'), operate:'BETWEEN', sortable: true},
                        {field: 'dif', title: __('Dif'), operate:false},
                        {field: 'dea', title: __('Dea'), operate:false},
                        {field: 'status_text', title: __('Status'), operate:false},
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