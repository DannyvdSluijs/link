window.ginger_type_name = function (gingerType, availableGingerTypes) {
    var gingerTypeObj = availableGingerTypes.findBy('value', gingerType);

    return (typeof gingerTypeObj !== "undefined")? gingerTypeObj.label : gingerType;
};

window.connector_name = function (connectorId, availableConnectors) {
    var connector = availableConnectors[connectorId];

    return (typeof  connector != "undefined")? connector.name : connectorId;
};

window.connector_icon = function (connectorId, availableConnectors, defaultIcon) {
    if (typeof defaultIcon == "undefined") defaultIcon = "glyphicon-cog";

    var connector = availableConnectors[connectorId];

    return (typeof  connector != "undefined" && typeof connector['icon'] != "undefined")? connector.icon : defaultIcon;
};

window.hash_find_by = function(hash, key, value) {
    var found = null;

    $.each(hash, function(hashKey, hashValue) {
        if (hashValue[key] == value) {
            found = hashValue;
            return false;
        }
    });

    return found;
};

//jQuery Additions
(function( $ ) {

    $.postJSON = function(url, data, settings) {
        if (typeof settings == 'undefined') settings = {};
        return $.ajax(url, $.extend({
            contentType : 'application/json; charset=UTF-8',
            type: "POST",
            data : JSON.stringify(data),
            dataType : 'json',
            dataFilter : function (data, dataType) {
                if (! data && dataType == "json") return "{}";
                return data;
            }
        }, settings))
    }

    $.putJSON = function(url, data, settings) {
        if (typeof settings == 'undefined') settings = {};
        return $.ajax(url, $.extend({
            contentType : 'application/json; charset=UTF-8',
            type: "PUT",
            data : JSON.stringify(data),
            dataType : 'json',
            dataFilter : function (data, dataType) {
                if (! data && dataType == "json") return "{}";
                return data;
            }
        }, settings))
    }

    $.failNotify = function(xhr) {
        if (typeof xhr.responseJSON == 'undefined') {
            xhr.responseJSON = {status : 500, title : 'Unknown Server Error', detail : 'Unknown Server response received'}
        }

        $.notify('['+ xhr.responseJSON.status +' '+xhr.responseJSON.title+'] '+xhr.responseJSON.detail, {
            className : 'error',
            clickToHide: true,
            autoHide: false
        });
    }

})( jQuery );


//Settings
$(function() {
    $.notify.defaults({
        globalPosition: 'bottom left'
    });
});

