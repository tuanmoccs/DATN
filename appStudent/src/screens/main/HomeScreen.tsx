import React, {useState, useCallback} from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  ScrollView,
  StatusBar,
  RefreshControl,
  ActivityIndicator,
} from 'react-native';
import {useAuth} from '../../contexts/AuthContext';
import {useFocusEffect, useNavigation} from '@react-navigation/native';
import {NativeStackNavigationProp} from '@react-navigation/native-stack';
import classService, {ClassInfo} from '../../services/classService';
import {MainStackParamList} from '../../navigation/MainNavigator';

type NavigationProp = NativeStackNavigationProp<MainStackParamList, 'MainTabs'>;

const HomeScreen: React.FC = () => {
  const {user, logout} = useAuth();
  const navigation = useNavigation<NavigationProp>();
  const [classes, setClasses] = useState<ClassInfo[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchData = async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    else setLoading(true);
    try {
      const response = await classService.getMyClasses();
      if (response.success) setClasses(response.data || []);
    } catch (error) {
      console.error('Error fetching home data:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useFocusEffect(
    useCallback(() => {
      fetchData();
    }, []),
  );

  const activeClasses = classes.filter(c => c.enrollment_status !== 'pending');
  const pendingClasses = classes.filter(c => c.enrollment_status === 'pending');

  const getInitial = (name?: string) =>
    name?.charAt(0)?.toUpperCase() || '?';

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0D47A1" />

      {/* Header */}
      <View style={styles.header}>
        <View style={styles.headerTop}>
          <View style={styles.headerLeft}>
            <View style={styles.avatar}>
              <Text style={styles.avatarText}>{getInitial(user?.name)}</Text>
            </View>
            <View>
              <Text style={styles.greeting}>Xin chào,</Text>
              <Text style={styles.userName}>{user?.name}</Text>
            </View>
          </View>
          <TouchableOpacity style={styles.logoutBtn} onPress={logout}>
            <Text style={styles.logoutIcon}>↗</Text>
          </TouchableOpacity>
        </View>

        {/* Stats */}
        <View style={styles.statsRow}>
          <View style={styles.statBox}>
            <Text style={styles.statNumber}>{activeClasses.length}</Text>
            <Text style={styles.statLabel}>Lớp đang học</Text>
          </View>
          <View style={styles.statDivider} />
          <View style={styles.statBox}>
            <Text style={styles.statNumber}>{pendingClasses.length}</Text>
            <Text style={styles.statLabel}>Chờ duyệt</Text>
          </View>
          <View style={styles.statDivider} />
          <View style={styles.statBox}>
            <Text style={styles.statNumber}>
              {classes.reduce((sum, c) => sum + (c.lesson_count || 0), 0)}
            </Text>
            <Text style={styles.statLabel}>Bài học</Text>
          </View>
        </View>
      </View>

      {/* Content */}
      <ScrollView
        style={styles.content}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => fetchData(true)}
            colors={['#0D47A1']}
          />
        }>
        {loading ? (
          <View style={styles.loadingBox}>
            <ActivityIndicator size="large" color="#0D47A1" />
          </View>
        ) : (
          <>
            {/* Quick Actions */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Truy cập nhanh</Text>
              <View style={styles.quickActions}>
                <TouchableOpacity
                  style={styles.actionCard}
                  activeOpacity={0.7}
                  onPress={() => {
                    // Navigate to classes tab
                  }}>
                  <View style={[styles.actionIcon, {backgroundColor: '#E3F2FD'}]}>
                    <Text style={styles.actionEmoji}>📚</Text>
                  </View>
                  <Text style={styles.actionText}>Lớp học</Text>
                </TouchableOpacity>
                <TouchableOpacity style={styles.actionCard} activeOpacity={0.7}>
                  <View style={[styles.actionIcon, {backgroundColor: '#E8F5E9'}]}>
                    <Text style={styles.actionEmoji}>📝</Text>
                  </View>
                  <Text style={styles.actionText}>Bài tập</Text>
                </TouchableOpacity>
                <TouchableOpacity style={styles.actionCard} activeOpacity={0.7}>
                  <View style={[styles.actionIcon, {backgroundColor: '#FFF3E0'}]}>
                    <Text style={styles.actionEmoji}>📊</Text>
                  </View>
                  <Text style={styles.actionText}>Kết quả</Text>
                </TouchableOpacity>
              </View>
            </View>

            {/* Recent Classes */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Lớp học gần đây</Text>
              {activeClasses.length === 0 ? (
                <View style={styles.emptyCard}>
                  <Text style={styles.emptyText}>
                    Chưa có lớp học nào. Hãy tham gia lớp từ tab "Lớp học".
                  </Text>
                </View>
              ) : (
                activeClasses.slice(0, 3).map(cls => (
                  <TouchableOpacity
                    key={cls.id}
                    style={styles.classCard}
                    activeOpacity={0.7}
                    onPress={() =>
                      navigation.navigate('ClassDetail', {classId: cls.id})
                    }>
                    <View style={styles.classIconBox}>
                      <Text style={styles.classIconText}>
                        {getInitial(cls.name)}
                      </Text>
                    </View>
                    <View style={styles.classInfo}>
                      <Text style={styles.className} numberOfLines={1}>
                        {cls.name}
                      </Text>
                      <Text style={styles.classMeta}>
                        {cls.teacher?.name} · {cls.lesson_count || 0} bài học
                      </Text>
                    </View>
                    <Text style={styles.classArrow}>›</Text>
                  </TouchableOpacity>
                ))
              )}
            </View>

            {/* Account Info */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Tài khoản</Text>
              <View style={styles.accountCard}>
                <View style={styles.accountRow}>
                  <Text style={styles.accountLabel}>Họ tên</Text>
                  <Text style={styles.accountValue}>{user?.name}</Text>
                </View>
                <View style={styles.accountDivider} />
                <View style={styles.accountRow}>
                  <Text style={styles.accountLabel}>Email</Text>
                  <Text style={styles.accountValue}>{user?.email}</Text>
                </View>
                <View style={styles.accountDivider} />
                <View style={styles.accountRow}>
                  <Text style={styles.accountLabel}>Vai trò</Text>
                  <View style={styles.roleBadge}>
                    <Text style={styles.roleText}>Học sinh</Text>
                  </View>
                </View>
              </View>
            </View>

            {/* Logout */}
            <TouchableOpacity
              style={styles.logoutButton}
              onPress={logout}
              activeOpacity={0.8}>
              <Text style={styles.logoutButtonText}>Đăng xuất</Text>
            </TouchableOpacity>

            <View style={{height: 32}} />
          </>
        )}
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F0F4F8',
  },
  header: {
    backgroundColor: '#0D47A1',
    paddingTop: 16,
    paddingBottom: 20,
    paddingHorizontal: 20,
  },
  headerTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 20,
  },
  headerLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  avatar: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.15)',
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 2,
    borderColor: 'rgba(255,255,255,0.3)',
  },
  avatarText: {
    fontSize: 18,
    fontWeight: '700',
    color: '#FFFFFF',
  },
  greeting: {
    fontSize: 13,
    color: 'rgba(255,255,255,0.7)',
  },
  userName: {
    fontSize: 18,
    fontWeight: '700',
    color: '#FFFFFF',
  },
  logoutBtn: {
    width: 36,
    height: 36,
    borderRadius: 8,
    backgroundColor: 'rgba(255,255,255,0.12)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  logoutIcon: {
    fontSize: 18,
    color: '#FFFFFF',
  },
  statsRow: {
    flexDirection: 'row',
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderRadius: 8,
    padding: 14,
  },
  statBox: {
    flex: 1,
    alignItems: 'center',
  },
  statDivider: {
    width: 1,
    backgroundColor: 'rgba(255,255,255,0.15)',
  },
  statNumber: {
    fontSize: 22,
    fontWeight: '700',
    color: '#FFFFFF',
    marginBottom: 2,
  },
  statLabel: {
    fontSize: 11,
    color: 'rgba(255,255,255,0.7)',
    fontWeight: '500',
  },
  content: {
    flex: 1,
  },
  loadingBox: {
    paddingVertical: 60,
    alignItems: 'center',
  },
  section: {
    paddingHorizontal: 20,
    marginTop: 20,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: '#0F172A',
    marginBottom: 12,
  },
  quickActions: {
    flexDirection: 'row',
    gap: 12,
  },
  actionCard: {
    flex: 1,
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
    padding: 14,
    alignItems: 'center',
    shadowColor: '#0D47A1',
    shadowOffset: {width: 0, height: 1},
    shadowOpacity: 0.06,
    shadowRadius: 4,
    elevation: 2,
  },
  actionIcon: {
    width: 40,
    height: 40,
    borderRadius: 8,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 8,
  },
  actionEmoji: {
    fontSize: 20,
  },
  actionText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#334155',
  },
  emptyCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
    padding: 20,
    alignItems: 'center',
  },
  emptyText: {
    fontSize: 13,
    color: '#64748B',
    textAlign: 'center',
    lineHeight: 20,
  },
  classCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
    padding: 14,
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
    shadowColor: '#0D47A1',
    shadowOffset: {width: 0, height: 1},
    shadowOpacity: 0.04,
    shadowRadius: 3,
    elevation: 1,
    borderLeftWidth: 3,
    borderLeftColor: '#1565C0',
  },
  classIconBox: {
    width: 40,
    height: 40,
    borderRadius: 8,
    backgroundColor: '#E3F2FD',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  classIconText: {
    fontSize: 16,
    fontWeight: '700',
    color: '#0D47A1',
  },
  classInfo: {
    flex: 1,
  },
  className: {
    fontSize: 14,
    fontWeight: '600',
    color: '#0F172A',
    marginBottom: 2,
  },
  classMeta: {
    fontSize: 12,
    color: '#64748B',
  },
  classArrow: {
    fontSize: 20,
    color: '#94A3B8',
    marginLeft: 8,
  },
  accountCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
    padding: 16,
    shadowColor: '#0D47A1',
    shadowOffset: {width: 0, height: 1},
    shadowOpacity: 0.04,
    shadowRadius: 3,
    elevation: 1,
  },
  accountRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 6,
  },
  accountDivider: {
    height: 1,
    backgroundColor: '#F1F5F9',
    marginVertical: 4,
  },
  accountLabel: {
    fontSize: 13,
    color: '#64748B',
    fontWeight: '500',
  },
  accountValue: {
    fontSize: 13,
    color: '#0F172A',
    fontWeight: '600',
  },
  roleBadge: {
    backgroundColor: '#E3F2FD',
    paddingHorizontal: 10,
    paddingVertical: 3,
    borderRadius: 4,
  },
  roleText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#0D47A1',
  },
  logoutButton: {
    marginHorizontal: 20,
    marginTop: 20,
    backgroundColor: '#DC2626',
    borderRadius: 8,
    paddingVertical: 14,
    alignItems: 'center',
  },
  logoutButtonText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '600',
  },
});

export default HomeScreen;
