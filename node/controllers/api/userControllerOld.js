const db = require('../../models')
var format = require('date-format');
const Sequelize = require('sequelize');
const UserModel = db.user
const jwt = require('jsonwebtoken');
const { successResponse, errorResponse } = require('../../utils/response');
const { HTTP_NOT_ACCEPTABLE } = require('../../constants');


// 1. Betting

const addUser = async (req, res) => {
  if (!req.body.mobile || !req.body.name || !req.body.password) {
    return res.status(200).send({ code: 100, message: "Invalid Parameter" })
  }
  const user = await getUser(req.body.mobile)
  if (user) {
    return res.status(200).send({ code: 404, message: "User Already exists." })
  }
  let info = {
    name: req.body.name,
    mobile: req.body.mobile,
    user_type: 0,
    password: req.body.password,
    added_date: format.asString(new Date()),
  }
  const result = await UserModel.create(info)
  res.status(200).send(result)

}

const login = async (req, res) => {
  if (!req.body.mobile || !req.body.password) {
    return res.status(200).send({ code: 100, message: "Invalid Parameter" })
  }
  try {
    const user = await UserModel.findOne({ mobile: req.body.mobile });
    if (user) {
      const userData = { ...user.toJSON() };
      if (req.body.password == user.password) {
        delete userData.password;
        jwt.sign({ userData }, process.env.JWTKEY, (err, token) => {
          if (err) {
            return res.status(200).json({
              code: '406',
              message: err,
            });
          }
          else {
            return res.status(200).json({
              code: '200',
              message: 'You Have Successfully Logged In',
              userData,
              token: token,
            });
          }
        })

      } else {
        return res.status(200).json({
          code: '406',
          message: 'Please Enter Valid Password',
        });
      }
    } else {
      return res.status(200).json({
        code: '406',
        message: 'Please Enter Valid Mobile Number',
      });
    }
  } catch (error) {
    console.error('Error during login:', error);
    return res.status(500).json({
      code: '500',
      message: error,
    });
  }
}

// 3. get user
const getUser = (mobile) => {
  return UserModel.findOne({ where: { mobile: mobile } })
}

// 3. get user
const getUserLogin = (req) => {
  return UserModel.findOne({
    where: {
      password: req.body.password, [Sequelize.or]: [
        { mobile: req.body.user_name },
        { email: req.body.user_name }
      ]
    }
  })
}

const userInfo = async (req, res) => {
  try {
    const user = req.user;
    return successResponse(res, user.toJSON());
  } catch (error) {
    return errorResponse(res, error.message, HTTP_NOT_ACCEPTABLE);
  }

  res.status(200).send(user)

}


// public function login_post()
// {
//     $user = $this->Users_model->LoginUser($this->data['mobile'],$this->data['password']);
//     if($user)
//     {
//         if($user[0]->status==1)
//         {
//             $data['message'] = 'You are blocked, Please contact to admin';
//             $data['code'] = HTTP_NOT_FOUND;
//             $this->response($data, HTTP_OK);
//             exit();
//         }

//         $data['message'] = 'Success';
//         $data['user_data'] = $user;
//         $data['code'] = HTTP_OK;
//         $this->response($data, HTTP_OK);
//         exit();
//     }
//     else
//     {
//         if($this->Users_model->UserProfileByMobile($this->data['mobile']))
//         {
//             $data['message'] = 'Incorrect Password';
//             $data['code'] = 408;
//             $this->response($data, HTTP_OK);
//             exit();
//         }
//         else
//         {
//             $data['message'] = 'User Not Found With This Mobile Number';
//             $data['code'] = HTTP_NOT_FOUND;
//             $this->response($data, HTTP_OK);
//             exit();
//         }
//     }
// }


// 5. delete product by id

const deleteUser = async (req, res) => {

  let id = req.params.id

  await Product.destroy({ where: { id: id } })

  res.status(200).send('Product is deleted !')

}

const dailyAttendenceReport = async (req, res) => {
  const user = req.user;
  const attendencesBonuses = await db.AttendenceBonus.findAll({});
  const startOfYesterday = new Date();
  startOfYesterday.setDate(startOfYesterday.getDate() - 1);
  startOfYesterday.setHours(0, 0, 0, 0);

  const endOfYesterday = new Date();
  endOfYesterday.setDate(endOfYesterday.getDate() - 1);
  endOfYesterday.setHours(23, 59, 59, 999);

  const lastAttendenceBonus = await db.AttendenceBonusLog.findOne({
    where: {
      added_date: {
        [Sequelize.Op.between]: [startOfYesterday, endOfYesterday]
      },
      user_id: user.id
    },
    order: [['added_date', 'DESC']],
    attributes: ["day", "id"]
  });

  let daysCollected = 0;
  if (lastAttendenceBonus) {
    daysCollected = lastAttendenceBonus.day;
  }

  const attendenceFinalArray = [];
  for (let index = 0; index < attendencesBonuses.length; index++) {
    const element = attendencesBonuses[index].toJSON();
    if (element.id <= daysCollected) {
      element.collected = 1;
    } else {
      element.collected = 0;
    }
    if (element.id == (daysCollected + 1)) {
      element.today_attendence = 1;
    }
    attendenceFinalArray.push(element);
  }

  return successResponse(res, { data: attendenceFinalArray, todays_bet: user.todays_bet });
}

module.exports = {
  addUser,
  login,
  userInfo,
  dailyAttendenceReport
}