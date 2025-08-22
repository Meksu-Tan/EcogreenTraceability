function createJSTree(id, jsondata){
    $(id).jstree("destroy").empty();
    $(id).jstree({
        "core" : {
            "animation" : 0,
            "check_callback" : true,
            "themes" : { "stripes" : true },
            "data" : jsondata,
        },
        "types" : {
            "#" : {
                "max_children" : -1,
                "max_depth" : -1,
            },
            "root" : {
                "icon" : "ti-folder",
                "valid_children" : ["default", "file"]
            },
            "default" : {
                "icon" : "ti-folder",
                "valid_children" : ["default", "file"]
            },
            "file" : {
                 "icon" : "ti-folder",
                 "valid_children" : []
             },
        },

        "plugins" : [ "state", "types", "wholerow", "sort" ],
    });

}
