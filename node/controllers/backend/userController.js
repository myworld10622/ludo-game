const db = require('../../models')
const jwt = require('jsonwebtoken');
const UserModel = db.user;
const AviatorBetModel = db.aviator;
const { HTTP_OK, HTTP_SERVER_ERROR, HTTP_NOT_FOUND } = require('../../constants');



const userCount = async (req, res) => {
    const users_count = await UserModel.count({ where: { isDeleted: false } });
    if (users_count) {
        return res.status(HTTP_OK).json({
            code: HTTP_OK,
            users_count: users_count,
        });
    } else {
        return res.status(HTTP_OK).json({
            code: HTTP_SERVER_ERROR,
            message: 'Something went wrong'
        });
    }

};

const listView = async (req, res) => {
    const users_list = await UserModel.findAll({ where: { isDeleted: false } })
    if (users_list) {
        return res.status(HTTP_OK).json({
            code: HTTP_OK,
            users_list: users_list,
        });
    } else {
        return res.status(HTTP_OK).json({
            code: HTTP_NOT_FOUND,
            message: 'Something went wrong'
        });
    }
}

const userView=async (req, res)=>{
    if (!req.body.user_id) {
        return res.status(HTTP_OK).json({
          code: HTTP_NOT_FOUND,
          message: 'Please Provide User Id',
        });
      }
    const user = await AviatorBetModel.findAll({ where: { user_id: req.body.user_id } })
    if (user) {
        return res.status(HTTP_OK).json({
            code: HTTP_OK,
            user: user,
        });
    } else {
        return res.status(HTTP_OK).json({
            code: HTTP_NOT_FOUND,
            message: 'Something went wrong'
        });
    }
}


module.exports = {
    listView,
    userCount,
    userView
}