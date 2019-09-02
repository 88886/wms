const gm = require('gm').subClass({imageMagick: true});
const {query, insert} = require('../database/query');
const path = require('path');
const log = require('../utils/winston');

let drawImg = (billCode, code, codeMsg, resolve) => {
    try {
        selectType[codeMsg.type](billCode, code, codeMsg, resolve);
    }
    catch (err) {
        log.logger.info({timestamp: new Date().toLocaleString(),msg: "draw图片失败！"});
        resolve({status: false, msg: 'catch,draw图片失败'});
    }
}

let selectType = {
    'ZTO': (billCode, code, codeMsg, resolve) => {
        let imagesPath =  path.resolve(__dirname,'..');
        let addresslength = codeMsg._receivAddress.split('').length;
        let messageLength = codeMsg.message.split('').length;
        let _receivAddress1, _receivAddress2,message1,message2;
        if(addresslength>25){
           _receivAddress1 = codeMsg._receivAddress.substring(0,26);
           _receivAddress2 =codeMsg._receivAddress.substring(26,addresslength)
        } else {
           _receivAddress1 = codeMsg._receivAddress;
           _receivAddress2 = '';
        }
        if(messageLength>25){
            message1 = codeMsg.message.substring(0,26);
            message2 = codeMsg.message.substring(26,addresslength);
        } else {
            message1 = codeMsg.message;
            message2 = '';
        }

        gm('./public/images/ZTO_template.jpg')
            .font('./public/font/microsoftYaHei_bold.ttf')
            .fontSize('110')
            .drawText(300, 260, codeMsg.getmark.mark)
            .fontSize('68')
            .drawText(300, 1076, billCode)
            .draw(`image Over 56, 814 1080, 180 "./public/images/ZTO_barcode/ZTO_barcode${code}.jpg"`)
            .draw(`image Over 580, 1333 600, 110 "./public/images/ZTO_barcode/ZTO_barcode${code}.jpg"`)
            .fontSize('30')
            .drawText(50,400, codeMsg.getmark.bagAddr)
            .drawText(800,400, `${codeMsg.order_id}`)
            .drawText(680,1486, billCode)
            .drawText(140, 480, `${codeMsg._addressee}`)
            .drawText(140, 540, _receivAddress1+"\n"+_receivAddress2)
            .drawText(140, 696, `${codeMsg._sender}`)
            .drawText(140, 760, `${codeMsg._mailAddress}`)
            .drawText(140, 1540, `${codeMsg._addressee}`)
            .drawText(140, 1610, _receivAddress1+"\n"+_receivAddress2)
            .drawText(140, 1742, `${codeMsg._sender}`)
            .drawText(140, 1812, `${codeMsg._mailAddress}`)
            .drawText(140, 1892, message1+"\n"+message2)
            .write(`./public/images/ZTO_order/ZTO_order${code}.jpg`, async (err) => {
                if (!err) {
                    resolve({status: true, msg: '生成图片成功'});
                    /*update wms_shoporders set logistics_status = ? , logistics_img = ? , logistics_num = ?   where  id = ?',[2,`${imagesPath}/images/ZTO_order/ZTO_order${code}.jpg`, code , codeMsg.id  new Date().toLocaleString()*/
                    let res = await query('update wms_shopdismantleorder set logistics_status = ? , logistics_img = ? , logistics_num = ?   where  id = ?',[2,`${imagesPath}/images/ZTO_order/ZTO_order${code}.jpg`, code , codeMsg.id ]);
                } else {
                    log.logger.info({timestamp: new Date().toLocaleString(),msg:err.message || "draw图片失败！"});
                    resolve({status: false, msg: 'draw图片失败'});
                }
            });
    }
}

module.exports = {
    drawImg
}