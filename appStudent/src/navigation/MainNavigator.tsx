import React from 'react';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {createBottomTabNavigator} from '@react-navigation/bottom-tabs';
import {Text, StyleSheet} from 'react-native';
import HomeScreen from '../screens/main/HomeScreen';
import ClassesScreen from '../screens/main/ClassesScreen';
import ClassDetailScreen from '../screens/main/ClassDetailScreen';

export type MainTabParamList = {
  HomeTab: undefined;
  ClassesTab: undefined;
};

export type MainStackParamList = {
  MainTabs: undefined;
  ClassDetail: {classId: number};
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
        headerStyle: {backgroundColor: '#FFFFFF', elevation: 1, shadowOpacity: 0.1},
        headerTitleStyle: {fontWeight: '600', color: '#1F2937'},
        tabBarActiveTintColor: '#2563EB',
        tabBarInactiveTintColor: '#9CA3AF',
        tabBarStyle: {
          backgroundColor: '#FFFFFF',
          borderTopWidth: 1,
          borderTopColor: '#F3F4F6',
          paddingBottom: 8,
          paddingTop: 8,
          height: 60,
        },
        tabBarLabelStyle: {fontSize: 12, fontWeight: '500'},
      }}>
      <Tab.Screen
        name="HomeTab"
        component={HomeScreen}
        options={{
          title: 'Trang chủ',
          tabBarIcon: ({focused}) => <TabIcon label="🏠" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="ClassesTab"
        component={ClassesScreen}
        options={{
          title: 'Lớp học',
          tabBarIcon: ({focused}) => <TabIcon label="📚" focused={focused} />,
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
    </Stack.Navigator>
  );
};

const styles = StyleSheet.create({
  tabIcon: {
    fontSize: 22,
    opacity: 0.6,
  },
  tabIconActive: {
    opacity: 1,
  },
});

export default MainNavigator;
