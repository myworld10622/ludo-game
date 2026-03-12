const { HTTP_OK, HTTP_SERVER_ERROR } = require('../../constants');
const db = require('../../models')
const jwt = require('jsonwebtoken');
const UserModel = db.user;
const AviatorBetModel = db.aviator;
const AdminModel = db.admin;

const aviatorReports = async (req, res) => {
    const aviator_reports = await AviatorBetModel.findAll({
        include: { model: UserModel, required: true }
    });
    if (aviator_reports) {
        return res.status(200).json({
            code: '200',
            aviator_reports: aviator_reports,
        });
    } else {
        return res.status(200).json({
            code: '500',
            message: 'Something went wrong'
        });
    }

};
const updateSetting = async (req, res) => {
    try {
        const id = req.body.id;
        const updateObject = {
            ...req.body,
        };
        if (req.files) {
            if (req.files.app_url && req.files.app_url.length > 0) {
                app_url = req.files.app_url[0].path;
                updateObject.app_url = app_url;

            }
            if (req.files.logo && req.files.logo.length > 0) {
                logo = req.files.logo[0].path;
                updateObject.logo = logo;
            }
        }     
        const updatedSetting = await AdminModel.update(
            updateObject,
            {
                where: {
                    id: id,
                },
            }
        );
        if (updatedSetting) {
            return res.status(HTTP_OK).json({
                code: HTTP_OK,
                message: 'Setting updated successfully',
            });
        }
        else {
            return res.status(HTTP_OK).json({
                code: HTTP_SERVER_ERROR,
                message: 'Failed to update setting',
            });
        }
    }
    catch (e) {
        console.log(e)
        return res.status(HTTP_SERVER_ERROR).json({
            code: HTTP_SERVER_ERROR,
            message: e,
        });
    }
};

module.exports = {
    aviatorReports,
    updateSetting,
}