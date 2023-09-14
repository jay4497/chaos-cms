define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'content/index' + location.search,
                    add_url: 'content/add',
                    edit_url: 'content/edit',
                    del_url: 'content/del',
                    multi_url: 'content/multi',
                    import_url: 'content/import',
                    dragsort_url: '',
                    table: 'content',
                }
            });

            $('.btn-add').data('area', ['100%', '100%']);

            var table = $("#table");
            table.on('post-body.bs.table', function() {
                $('.btn-editone').data('area', ['100%', '100%']);
                $('.btn-review').data('area', ['100%', '100%']);
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                fixedColumns: true,
                fixedRightNumber: 1,
                search: false,
                dragsort: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        //{field: 'category_id', title: __('Category_id'), visible: false},
                        {field: 'category', title: __('Category'), operate: 'LIKE'},
                        {field: 'title', title: __('Title'), operate: 'LIKE', align: 'left'},
                        //{field: 'keywords', title: __('Keywords'), operate: 'LIKE'},
                        //{field: 'subtitle', title: __('Subtitle'), operate: 'LIKE'},
                        {field: 'top', title: __('Top'), formatter: Table.api.formatter.toggle, searchList: Config.toggleList},
                        {field: 'recommend', title: __('Recommend'), formatter: Table.api.formatter.toggle, searchList: Config.toggleList},
                        {field: 'views', title: __('Views'), operate: false},
                        //{field: 'weigh', title: __('Weigh'), operate: false},
                        {field: 'status', title: __('Status'), searchList: Config.statusList, formatter: function(val, row, index) {
                            var theList = Config.statusList;
                            if(theList.hasOwnProperty(val)) {
                                if(val === 0) {
                                    return theList[val] + '&nbsp;&nbsp;<a href="javascript:;" class="btn-change" data-id="' + row['id'] + '" data-params="status=2" data-toggle="tooltip" title="点击发布"><i class="fa fa-send"></i> </a>';
                                }
                                return theList[val];
                            }
                            return __('unknown');
                        }},
                        //{field: 'admin_id', title: __('Admin_id')},
                        {field: 'admin', title: __('Admin'), operate: 'LIKE'},
                        //{field: 'reviewed_by', title: __('Reviewed_by')},
                        //{field: 'reviewed_by_name', title: __('Reviewed_by_name'), operate: 'LIKE'},
                        {field: 'created_at', title: __('Created_at'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        //{field: 'updated_at', title: __('Updated_at'), operate: false, addclass:'datetimerange', autocomplete:false},
                        //{field: 'reviewed_at', title: __('Reviewed_at'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'review',
                                    title: __('go review'),
                                    icon: 'fa fa-check',
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-success btn-review btn-dialog',
                                    url: 'content/review'
                                },
                                Table.button.edit,
                                Table.button.del
                            ]
                        }
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
            $('#c-category_id').change();
        },
        review: function() {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                $(document).on("change", "#c-category_id", function () {
                    var type = $(this).find('option:selected').data('type');
                    console.log(type);
                    if(type === 'page') {
                        $('#content-ex').addClass('hide');
                    }
                });

                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
