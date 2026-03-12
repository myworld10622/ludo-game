const express = require('express');

const multer = require('multer')
const fs = require('fs'); 

// import controllers review, products
const aviatorController = require('../controllers/api/aviatorControllerOld.js')
const userController = require('../controllers/api/userControllerOld.js')

// backend controllers
const admincontrollerBackend = require('../controllers/backend/adminController.js')
const usercontrollerBackend = require('../controllers/backend/userController.js')
const aviatorcontrollerBackend = require('../controllers/backend/aviatorController.js')

const router = require('express').Router()
const jwt = require('jsonwebtoken');



function verifyToken(req, res, next) {
    // console.log("middleware node");
    let token = req.headers.authorization;
    if (token) {
        token = token.split(' ')[1];
        jwt.verify(token, process.env.JWTKEY, (err, decoded) => {
            if (err) {
                return res.status(401).json({
                    code: '401',
                    message: 'Token is not valid',
                });
            } else {
                req.user = decoded;
                next();
            }
        });
    } else {
        return res.status(401).json({
            code: '401',
            message: 'Please add a valid token',
        });
    }
  }
  
  const storage = (uploadPath) => {
    return multer.diskStorage({
      destination: (req, file, cb) => {        
        const uploadFolderPath = `uploads/${uploadPath}`;
        if (!fs.existsSync(uploadFolderPath)) {
          fs.mkdirSync(uploadFolderPath, { recursive: true });
        }
        cb(null, uploadFolderPath);
      },
      filename: (req, file, cb) => {
        console.log(file)
      const fileExt = file.originalname.split('.').pop();
      const timestamp = Date.now();
      const uniqueFilename = `${timestamp}.${fileExt}`;
      cb(null, uniqueFilename);
      },
    });
  }
  
const upload = (uploadPath) => {
  // console.log('at upload function',uploadPath)
  return multer({ storage: storage(uploadPath) });
}




// user routers api
router.post('/addBet', aviatorController.addBet)
router.post('/redeem', aviatorController.redeem)
router.post('/cancelBet', aviatorController.cancelBet)
router.post('/userById', aviatorController.userInfo)
router.post('/addUser', userController.addUser)
router.post('/user-login', userController.login)



// // user routers
// router.get('/:id', userController.getOneProduct)
// router.put('/:id', userController.updateProduct)
// router.delete('/:id', userController.deleteProduct)

// admin routers
router.post('/login', admincontrollerBackend.login)
router.post('/register',admincontrollerBackend.register)
router.get('/view-admin',admincontrollerBackend.viewAdmin)
router.post('/admin/profile',verifyToken,admincontrollerBackend.profile)
router.post('/admin/profile/update',verifyToken,admincontrollerBackend.updateProfile)



// user routers
router.get('/users-list',verifyToken,usercontrollerBackend.listView)
router.get('/users-count',verifyToken,usercontrollerBackend.userCount)
router.post('/user-view-bet',verifyToken,usercontrollerBackend.userView)

// aviator reports
router.get('/aviator-reports',verifyToken,aviatorcontrollerBackend.aviatorReports)

// setting update
router.post('/update-setting',verifyToken, upload('logo').fields([{ name: 'app_url' }, { name: 'logo' }]), aviatorcontrollerBackend.updateSetting);


module.exports = router