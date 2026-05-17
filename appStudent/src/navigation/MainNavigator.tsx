import React from 'react';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {createBottomTabNavigator} from '@react-navigation/bottom-tabs';
import {Text, StyleSheet} from 'react-native';
import HomeScreen from '../screens/main/HomeScreen';
import ClassesScreen from '../screens/main/ClassesScreen';
import ProfileScreen from '../screens/main/ProfileScreen';
import ClassDetailScreen from '../screens/main/ClassDetailScreen';
import LessonDetailScreen from '../screens/main/LessonDetailScreen';
import SlideViewerScreen from '../screens/main/SlideViewerScreen';
import QuizScreen from '../screens/main/QuizScreen';
import QuizResultScreen from '../screens/main/QuizResultScreen';
import AssignmentListScreen from '../screens/main/AssignmentListScreen';
import AssignmentDetailScreen from '../screens/main/AssignmentDetailScreen';
import AssignmentSubmitScreen from '../screens/main/AssignmentSubmitScreen';
import {SlideInfo} from '../services/lessonService';

export type MainTabParamList = {
  HomeTab: undefined;
  ClassesTab: undefined;
  ProfileTab: undefined;
};

export type MainStackParamList = {
  MainTabs: undefined;
  ClassDetail: {classId: number};
  LessonDetail: {lessonId: number};
  SlideViewer: {lessonId: number; slides: SlideInfo[]; currentSlide: number};
  QuizScreen: {quizId: number; lessonId: number};
  QuizResult: {attemptId: number; lessonId: number};
  AssignmentList: {classId: number; className: string};
  AssignmentDetail: {assignmentId: number; classId: number};
  AssignmentSubmit: {
    assignmentId: number;
    assignmentTitle: string;
    submissionType: string;
    maxScore: number;
  };
};

const Tab = createBottomTabNavigator<MainTabParamList>();
const Stack = createNativeStackNavigator<MainStackParamList>();

const TabIcon = ({label, focused}: {label: string; focused: boolean}) => (
  <Text style={[styles.tabIcon, focused && styles.tabIconActive]}>
    {label}
  </Text>
);

const MainTabs: React.FC = () => {
  return (
    <Tab.Navigator
      screenOptions={{
        headerStyle: {backgroundColor: '#0D47A1', elevation: 0, shadowOpacity: 0},
        headerTitleStyle: {fontWeight: '600', color: '#FFFFFF'},
        tabBarActiveTintColor: '#0D47A1',
        tabBarInactiveTintColor: '#94A3B8',
        tabBarStyle: {
          backgroundColor: '#FFFFFF',
          borderTopWidth: 1,
          borderTopColor: '#E2E8F0',
          paddingBottom: 8,
          paddingTop: 8,
          height: 60,
        },
        tabBarLabelStyle: {fontSize: 11, fontWeight: '600'},
        headerShown: false,
      }}>
      <Tab.Screen
        name="HomeTab"
        component={HomeScreen}
        options={{
          title: 'Dashboard',
          tabBarIcon: ({focused}) => <TabIcon label="D" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="ClassesTab"
        component={ClassesScreen}
        options={{
          title: 'Lớp học',
          tabBarIcon: ({focused}) => <TabIcon label="C" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="ProfileTab"
        component={ProfileScreen}
        options={{
          title: 'Cá nhân',
          tabBarIcon: ({focused}) => <TabIcon label="P" focused={focused} />,
        }}
      />
    </Tab.Navigator>
  );
};

const MainNavigator: React.FC = () => {
  return (
    <Stack.Navigator
      screenOptions={{
        headerShown: false,
      }}>
      <Stack.Screen name="MainTabs" component={MainTabs} />
      <Stack.Screen name="ClassDetail" component={ClassDetailScreen} />
      <Stack.Screen name="LessonDetail" component={LessonDetailScreen} />
      <Stack.Screen name="SlideViewer" component={SlideViewerScreen} />
      <Stack.Screen name="QuizScreen" component={QuizScreen} />
      <Stack.Screen name="QuizResult" component={QuizResultScreen} />
      <Stack.Screen name="AssignmentList" component={AssignmentListScreen} />
      <Stack.Screen name="AssignmentDetail" component={AssignmentDetailScreen} />
      <Stack.Screen name="AssignmentSubmit" component={AssignmentSubmitScreen} />
    </Stack.Navigator>
  );
};

const styles = StyleSheet.create({
  tabIcon: {
    fontSize: 18,
    opacity: 0.6,
    fontWeight: '700',
  },
  tabIconActive: {
    opacity: 1,
  },
});

export default MainNavigator;
