import React, {useState, useEffect} from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  StatusBar,
  RefreshControl,
} from 'react-native';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import {NativeStackNavigationProp} from '@react-navigation/native-stack';
import classService, {ClassInfo} from '../../services/classService';
import {MainStackParamList} from '../../navigation/MainNavigator';

type ClassDetailRouteProp = RouteProp<MainStackParamList, 'ClassDetail'>;
type NavigationProp = NativeStackNavigationProp<MainStackParamList>;

type TabKey = 'info' | 'lessons' | 'assignments' | 'students';

const ClassDetailScreen: React.FC = () => {
  const route = useRoute<ClassDetailRouteProp>();
  const navigation = useNavigation<NavigationProp>();
  const {classId} = route.params;

  const [classData, setClassData] = useState<ClassInfo | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [activeTab, setActiveTab] = useState<TabKey>('info');

  const fetchDetail = async (isRefresh = false) => {
    if (isRefresh) {
      setRefreshing(true);
    } else {
      setLoading(true);
    }
    try {
      const response = await classService.getClassDetail(classId);
      if (response.success) {
        setClassData(response.data);
      }
    } catch (error: any) {
      console.error('Error fetching class detail:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchDetail();
  }, [classId]);

  const tabs: {key: TabKey; label: string; count?: number}[] = [
    {key: 'info', label: 'Thông tin'},
    {key: 'lessons', label: 'Bài học', count: classData?.lessons?.length || 0},
    {key: 'assignments', label: 'Bài tập'},
    {
      key: 'students',
      label: 'Thành viên',
      count: classData?.enrollment?.length || 0,
    },
  ];

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#2563EB" />
      </View>
    );
  }

  if (!classData) {
    return (
      <View style={styles.errorContainer}>
        <Text style={styles.errorIcon}>😔</Text>
        <Text style={styles.errorText}>Không thể tải thông tin lớp học</Text>
        <TouchableOpacity style={styles.retryButton} onPress={() => fetchDetail()}>
          <Text style={styles.retryButtonText}>Thử lại</Text>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0D47A1" />

      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity
          style={styles.backButton}
          onPress={() => navigation.goBack()}>
          <Text style={styles.backIcon}>←</Text>
        </TouchableOpacity>
        <View style={styles.headerContent}>
          <Text style={styles.headerTitle} numberOfLines={1}>
            {classData.name}
          </Text>
          <Text style={styles.headerSubtitle}>
            {classData.semester || 'Chưa xác định'} • Mã: {classData.code}
          </Text>
        </View>
      </View>

      {/* Tabs */}
      <View style={styles.tabBar}>
        {tabs.map(tab => (
          <TouchableOpacity
            key={tab.key}
            style={[styles.tab, activeTab === tab.key && styles.tabActive]}
            onPress={() => setActiveTab(tab.key)}>
            <Text
              style={[
                styles.tabText,
                activeTab === tab.key && styles.tabTextActive,
              ]}>
              {tab.label}
            </Text>
            {tab.count !== undefined && tab.count > 0 && (
              <View
                style={[
                  styles.tabBadge,
                  activeTab === tab.key && styles.tabBadgeActive,
                ]}>
                <Text
                  style={[
                    styles.tabBadgeText,
                    activeTab === tab.key && styles.tabBadgeTextActive,
                  ]}>
                  {tab.count}
                </Text>
              </View>
            )}
          </TouchableOpacity>
        ))}
      </View>

      {/* Tab Content */}
      <ScrollView
        style={styles.content}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => fetchDetail(true)}
            colors={['#2563EB']}
          />
        }
        showsVerticalScrollIndicator={false}>
        {activeTab === 'info' && renderInfoTab(classData)}
        {activeTab === 'lessons' && renderLessonsTab(classData, (lessonId: number) => {
          navigation.navigate('LessonDetail', {lessonId});
        })}
        {activeTab === 'assignments' && renderAssignmentsTab(classData, navigation)}
        {activeTab === 'students' && renderStudentsTab(classData)}
        <View style={styles.bottomSpace} />
      </ScrollView>
    </View>
  );
};

// ========== Tab: Thông tin ==========
const renderInfoTab = (classData: ClassInfo) => (
  <View style={styles.tabContent}>
    {/* Teacher Card */}
    <View style={styles.sectionCard}>
      <Text style={styles.sectionTitle}>👨‍🏫 Giáo viên</Text>
      <View style={styles.teacherCard}>
        <View style={styles.teacherAvatar}>
          <Text style={styles.teacherAvatarText}>
            {classData.teacher?.name?.charAt(0)?.toUpperCase() || '?'}
          </Text>
        </View>
        <View style={styles.teacherInfo}>
          <Text style={styles.teacherNameText}>
            {classData.teacher?.name || 'Chưa rõ'}
          </Text>
          <Text style={styles.teacherEmail}>
            {classData.teacher?.email || ''}
          </Text>
        </View>
      </View>
    </View>

    {/* Class Info */}
    <View style={styles.sectionCard}>
      <Text style={styles.sectionTitle}>📋 Thông tin lớp</Text>

      <View style={styles.infoRow}>
        <Text style={styles.infoLabel}>Mã lớp</Text>
        <Text style={styles.infoValueCode}>{classData.code}</Text>
      </View>

      <View style={styles.infoDivider} />

      <View style={styles.infoRow}>
        <Text style={styles.infoLabel}>Học kỳ</Text>
        <Text style={styles.infoValue}>
          {classData.semester || 'Chưa xác định'}
        </Text>
      </View>

      <View style={styles.infoDivider} />

      <View style={styles.infoRow}>
        <Text style={styles.infoLabel}>Trạng thái</Text>
        <View style={styles.statusActiveBadge}>
          <Text style={styles.statusActiveText}>
            {classData.status === 'active' ? 'Đang hoạt động' : classData.status}
          </Text>
        </View>
      </View>

      <View style={styles.infoDivider} />

      <View style={styles.infoRow}>
        <Text style={styles.infoLabel}>Số học sinh</Text>
        <Text style={styles.infoValue}>
          {classData.student_count || 0}
          {classData.max_students ? ` / ${classData.max_students}` : ''}
        </Text>
      </View>

      <View style={styles.infoDivider} />

      <View style={styles.infoRow}>
        <Text style={styles.infoLabel}>Số bài học</Text>
        <Text style={styles.infoValue}>{classData.lesson_count || 0}</Text>
      </View>
    </View>

    {/* Description */}
    {classData.description && (
      <View style={styles.sectionCard}>
        <Text style={styles.sectionTitle}>📝 Mô tả</Text>
        <Text style={styles.descriptionText}>{classData.description}</Text>
      </View>
    )}
  </View>
);

// ========== Tab: Bài học ==========
const renderLessonsTab = (classData: ClassInfo, onLessonPress: (lessonId: number) => void) => {
  const lessons = classData.lessons || [];

  if (lessons.length === 0) {
    return (
      <View style={styles.emptyTab}>
        <Text style={styles.emptyTabIcon}>📖</Text>
        <Text style={styles.emptyTabTitle}>Chưa có bài học</Text>
        <Text style={styles.emptyTabSubtitle}>
          Giáo viên chưa đăng bài học nào cho lớp này
        </Text>
      </View>
    );
  }

  return (
    <View style={styles.tabContent}>
      {lessons.map((lesson, index) => (
        <TouchableOpacity
          key={lesson.id}
          style={styles.lessonCard}
          activeOpacity={0.7}
          onPress={() => onLessonPress(lesson.id)}>
          <View style={styles.lessonNumber}>
            <Text style={styles.lessonNumberText}>{index + 1}</Text>
          </View>
          <View style={styles.lessonContent}>
            <Text style={styles.lessonTitle} numberOfLines={2}>
              {lesson.title}
            </Text>
            {lesson.description && (
              <Text style={styles.lessonDescription} numberOfLines={2}>
                {lesson.description}
              </Text>
            )}
          </View>
          <Text style={styles.lessonArrow}>›</Text>
        </TouchableOpacity>
      ))}
    </View>
  );
};

// ========== Tab: Bài tập ==========
const renderAssignmentsTab = (classData: ClassInfo, navigation: any) => {
  return (
    <View style={styles.tabContent}>
      <TouchableOpacity
        style={styles.assignmentNavCard}
        activeOpacity={0.7}
        onPress={() =>
          navigation.navigate('AssignmentList', {
            classId: classData.id,
            className: classData.name,
          })
        }>
        <View style={styles.assignmentNavIcon}>
          <Text style={styles.assignmentNavIconText}>📋</Text>
        </View>
        <View style={styles.assignmentNavContent}>
          <Text style={styles.assignmentNavTitle}>Xem bài tập</Text>
          <Text style={styles.assignmentNavSubtitle}>
            Xem danh sách bài tập, nộp bài và xem kết quả chấm điểm
          </Text>
        </View>
        <Text style={styles.assignmentNavArrow}>›</Text>
      </TouchableOpacity>
    </View>
  );
};

// ========== Tab: Thành viên ==========
const renderStudentsTab = (classData: ClassInfo) => {
  const enrollments = classData.enrollment || [];

  if (enrollments.length === 0) {
    return (
      <View style={styles.emptyTab}>
        <Text style={styles.emptyTabIcon}>👥</Text>
        <Text style={styles.emptyTabTitle}>Chưa có thành viên</Text>
        <Text style={styles.emptyTabSubtitle}>
          Chưa có học sinh nào trong lớp
        </Text>
      </View>
    );
  }

  return (
    <View style={styles.tabContent}>
      {/* Teacher */}
      <View style={styles.memberCard}>
        <View style={[styles.memberAvatar, styles.memberAvatarTeacher]}>
          <Text style={styles.memberAvatarText}>
            {classData.teacher?.name?.charAt(0)?.toUpperCase() || '?'}
          </Text>
        </View>
        <View style={styles.memberInfo}>
          <Text style={styles.memberName}>{classData.teacher?.name}</Text>
          <Text style={styles.memberRole}>Giáo viên</Text>
        </View>
      </View>

      {/* Students */}
      {enrollments.map((enrollment, index) => (
        <View key={enrollment.id} style={styles.memberCard}>
          <View style={styles.memberAvatar}>
            <Text style={styles.memberAvatarText}>
              {enrollment.user?.name?.charAt(0)?.toUpperCase() || '?'}
            </Text>
          </View>
          <View style={styles.memberInfo}>
            <Text style={styles.memberName}>{enrollment.user?.name}</Text>
            <Text style={styles.memberRole}>Học sinh</Text>
          </View>
          <Text style={styles.memberIndex}>#{index + 1}</Text>
        </View>
      ))}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F0F4F8',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F0F4F8',
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F0F4F8',
    padding: 40,
  },
  errorIcon: {
    fontSize: 48,
    marginBottom: 16,
  },
  errorText: {
    fontSize: 16,
    color: '#64748B',
    textAlign: 'center',
    marginBottom: 20,
  },
  retryButton: {
    backgroundColor: '#0D47A1',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 6,
  },
  retryButtonText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '600',
  },
  header: {
    backgroundColor: '#0D47A1',
    flexDirection: 'row',
    alignItems: 'center',
    paddingTop: (StatusBar.currentHeight || 0) + 12,
    paddingBottom: 16,
    paddingHorizontal: 16,
  },
  backButton: {
    width: 36,
    height: 36,
    borderRadius: 6,
    backgroundColor: 'rgba(255,255,255,0.15)',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  backIcon: {
    fontSize: 20,
    color: '#FFFFFF',
    fontWeight: '700',
  },
  headerContent: {
    flex: 1,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: '#FFFFFF',
  },
  headerSubtitle: {
    fontSize: 12,
    color: 'rgba(255,255,255,0.7)',
    marginTop: 2,
  },
  tabBar: {
    flexDirection: 'row',
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
  },
  tab: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 12,
    borderBottomWidth: 2,
    borderBottomColor: 'transparent',
    gap: 4,
  },
  tabActive: {
    borderBottomColor: '#0D47A1',
  },
  tabText: {
    fontSize: 13,
    fontWeight: '500',
    color: '#94A3B8',
  },
  tabTextActive: {
    color: '#0D47A1',
    fontWeight: '600',
  },
  tabBadge: {
    backgroundColor: '#E2E8F0',
    paddingHorizontal: 6,
    paddingVertical: 1,
    borderRadius: 4,
  },
  tabBadgeActive: {
    backgroundColor: '#E3F2FD',
  },
  tabBadgeText: {
    fontSize: 11,
    fontWeight: '600',
    color: '#64748B',
  },
  tabBadgeTextActive: {
    color: '#0D47A1',
  },
  content: {
    flex: 1,
  },
  tabContent: {
    padding: 16,
    gap: 10,
  },
  bottomSpace: {
    height: 24,
  },
  sectionCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
    padding: 16,
    shadowColor: '#0D47A1',
    shadowOffset: {width: 0, height: 1},
    shadowOpacity: 0.04,
    shadowRadius: 4,
    elevation: 1,
  },
  sectionTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: '#0F172A',
    marginBottom: 12,
  },
  teacherCard: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  teacherAvatar: {
    width: 44,
    height: 44,
    borderRadius: 8,
    backgroundColor: '#E3F2FD',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  teacherAvatarText: {
    fontSize: 18,
    fontWeight: '700',
    color: '#0D47A1',
  },
  teacherInfo: {
    flex: 1,
  },
  teacherNameText: {
    fontSize: 15,
    fontWeight: '600',
    color: '#0F172A',
    marginBottom: 2,
  },
  teacherEmail: {
    fontSize: 12,
    color: '#64748B',
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 4,
  },
  infoLabel: {
    fontSize: 13,
    color: '#64748B',
  },
  infoValue: {
    fontSize: 13,
    color: '#0F172A',
    fontWeight: '500',
  },
  infoValueCode: {
    fontSize: 14,
    color: '#0D47A1',
    fontWeight: '700',
    fontFamily: 'monospace',
    letterSpacing: 2,
  },
  infoDivider: {
    height: 1,
    backgroundColor: '#F1F5F9',
    marginVertical: 6,
  },
  statusActiveBadge: {
    backgroundColor: '#ECFDF5',
    borderRadius: 4,
    paddingHorizontal: 8,
    paddingVertical: 3,
  },
  statusActiveText: {
    color: '#065F46',
    fontSize: 12,
    fontWeight: '600',
  },
  descriptionText: {
    fontSize: 13,
    color: '#475569',
    lineHeight: 20,
  },
  lessonCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
    padding: 14,
    flexDirection: 'row',
    alignItems: 'center',
    shadowColor: '#0D47A1',
    shadowOffset: {width: 0, height: 1},
    shadowOpacity: 0.04,
    shadowRadius: 4,
    elevation: 1,
    borderLeftWidth: 3,
    borderLeftColor: '#1565C0',
  },
  lessonNumber: {
    width: 32,
    height: 32,
    borderRadius: 6,
    backgroundColor: '#E3F2FD',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  lessonNumberText: {
    fontSize: 14,
    fontWeight: '700',
    color: '#0D47A1',
  },
  lessonContent: {
    flex: 1,
  },
  lessonTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#0F172A',
    marginBottom: 2,
  },
  lessonDescription: {
    fontSize: 12,
    color: '#64748B',
    lineHeight: 17,
  },
  lessonArrow: {
    fontSize: 20,
    color: '#94A3B8',
    fontWeight: '300',
    marginLeft: 8,
  },
  assignmentNavCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    shadowColor: '#0D47A1',
    shadowOffset: {width: 0, height: 1},
    shadowOpacity: 0.05,
    shadowRadius: 4,
    elevation: 2,
    borderLeftWidth: 3,
    borderLeftColor: '#1565C0',
  },
  assignmentNavIcon: {
    width: 44,
    height: 44,
    borderRadius: 8,
    backgroundColor: '#E3F2FD',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  assignmentNavIconText: {
    fontSize: 22,
  },
  assignmentNavContent: {
    flex: 1,
  },
  assignmentNavTitle: {
    fontSize: 15,
    fontWeight: '600',
    color: '#0F172A',
    marginBottom: 3,
  },
  assignmentNavSubtitle: {
    fontSize: 12,
    color: '#64748B',
    lineHeight: 17,
  },
  assignmentNavArrow: {
    fontSize: 20,
    color: '#94A3B8',
    fontWeight: '300',
    marginLeft: 8,
  },
  memberCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
    padding: 12,
    flexDirection: 'row',
    alignItems: 'center',
    shadowColor: '#0D47A1',
    shadowOffset: {width: 0, height: 1},
    shadowOpacity: 0.04,
    shadowRadius: 4,
    elevation: 1,
  },
  memberAvatar: {
    width: 38,
    height: 38,
    borderRadius: 8,
    backgroundColor: '#ECFDF5',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  memberAvatarTeacher: {
    backgroundColor: '#E3F2FD',
  },
  memberAvatarText: {
    fontSize: 15,
    fontWeight: '700',
    color: '#065F46',
  },
  memberInfo: {
    flex: 1,
  },
  memberName: {
    fontSize: 14,
    fontWeight: '600',
    color: '#0F172A',
    marginBottom: 1,
  },
  memberRole: {
    fontSize: 12,
    color: '#64748B',
  },
  memberIndex: {
    fontSize: 12,
    color: '#94A3B8',
    fontWeight: '500',
  },
  emptyTab: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 60,
    paddingHorizontal: 40,
  },
  emptyTabIcon: {
    fontSize: 48,
    marginBottom: 16,
  },
  emptyTabTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#0F172A',
    marginBottom: 6,
  },
  emptyTabSubtitle: {
    fontSize: 13,
    color: '#94A3B8',
    textAlign: 'center',
    lineHeight: 20,
  },
});

export default ClassDetailScreen;
